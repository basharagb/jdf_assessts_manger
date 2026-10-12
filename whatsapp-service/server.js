if (typeof crypto === 'undefined') {
    global.crypto = require('crypto').webcrypto;
}

const express = require('express');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const QRCode = require('qrcode');
const fs = require('fs');
const path = require('path');
const http = require('http');
const https = require('https');
const pino = require('pino');
const {
    default: makeWASocket,
    DisconnectReason,
    fetchLatestBaileysVersion,
    getAggregateVotesInPollMessage,
    makeCacheableSignalKeyStore,
    useMultiFileAuthState,
} = require('@whiskeysockets/baileys');

const app = express();
const PORT = Number(process.env.WA_SERVICE_PORT || 3001);
const API_KEY = process.env.WA_API_KEY || 'connectra-wa-test-key';
const INSTANCE_NAME = String(process.env.WA_INSTANCE_NAME || '')
    .trim()
    .replace(/[^a-zA-Z0-9_-]/g, '');
const INSTANCE_SUFFIX = INSTANCE_NAME ? '-' + INSTANCE_NAME : (PORT === 3001 ? '' : '-' + PORT);
const LOG_FILE = process.env.WA_LOG_FILE
    ? path.resolve(process.env.WA_LOG_FILE)
    : path.join(__dirname, 'messages' + INSTANCE_SUFFIX + '.log');
const CONFIG_FILE = process.env.WA_CONFIG_FILE
    ? path.resolve(process.env.WA_CONFIG_FILE)
    : path.join(__dirname, 'service-config' + INSTANCE_SUFFIX + '.json');
const POLL_STORE_FILE = process.env.WA_POLL_STORE_FILE
    ? path.resolve(process.env.WA_POLL_STORE_FILE)
    : path.join(__dirname, 'poll-store' + INSTANCE_SUFFIX + '.json');
const AUTH_DIR = process.env.WA_AUTH_DIR
    ? path.resolve(process.env.WA_AUTH_DIR)
    : path.join(__dirname, 'auth_info' + INSTANCE_SUFFIX);
const logger = pino({ level: process.env.WA_LOG_LEVEL || 'silent' });

app.use(cors({ origin: true }));
app.use(express.json({ limit: '1mb' }));

const sendLimiter = rateLimit({
    windowMs: 60 * 1000,
    max: 20,
    message: { error: 'Too many requests. Max 20 messages per minute.' },
});

function loadJson(filePath, fallback) {
    try {
        if (!fs.existsSync(filePath)) {
            return fallback;
        }

        return JSON.parse(fs.readFileSync(filePath, 'utf8'));
    } catch (error) {
        console.error('[WA] Failed to load', path.basename(filePath), error.message);

        return fallback;
    }
}

function saveJson(filePath, payload) {
    fs.writeFileSync(filePath, JSON.stringify(payload, null, 2));
}

function requireApiKey(req, res, next) {
    const key = req.headers['x-api-key'] || req.query.key;
    if (key !== API_KEY) {
        return res.status(401).json({ error: 'Unauthorized' });
    }

    next();
}

function logMessage(direction, to, message, result = null) {
    const entry = {
        ts: new Date().toISOString(),
        direction,
        to,
        message: String(message || '').slice(0, 500),
        result,
    };

    fs.appendFileSync(LOG_FILE, JSON.stringify(entry) + '\n');
    console.log('[' + entry.ts + '] ' + direction + ' -> ' + to + ': ' + String(message || '').slice(0, 60));
}

function jidToPhone(value) {
    if (!value) {
        return '';
    }

    const raw = typeof value === 'string'
        ? value
        : value.remoteJid || value.participant || value.id || value._serialized || value.user || '';

    return String(raw)
        .replace(/:\d+(?=@|$)/, '')
        .replace(/@c\.us$/i, '')
        .replace(/@s\.whatsapp\.net$/i, '')
        .replace(/[^0-9]/g, '');
}

function normalizeChatId(phone) {
    const digits = String(phone || '').replace(/[^0-9]/g, '');
    return digits ? digits + '@s.whatsapp.net' : '';
}

function buildStoreKey(key) {
    if (!key || !key.remoteJid || !key.id) {
        return '';
    }

    return key.remoteJid + '|' + key.id;
}

function hasPollMessageContent(message) {
    return Boolean(
        message?.pollCreationMessage
        || message?.pollCreationMessageV2
        || message?.pollCreationMessageV3
    );
}

let runtimeConfig = loadJson(CONFIG_FILE, {
    serverWebhookUrl: '',
    serverWebhookKey: '',
});

let pollStore = loadJson(POLL_STORE_FILE, {});
let sock = null;
let status = 'disconnected';
let currentQR = null;
let myPhone = null;
let starting = false;
let manualDisconnect = false;
let reconnectTimer = null;
let lastError = null;

function statusPayload(includeQr = false) {
    return {
        status,
        connected: status === 'connected',
        info: myPhone ? { phone: myPhone } : null,
        qr: includeQr && status === 'qr_ready' ? currentQR : null,
        error: lastError,
    };
}

function prunePollStore() {
    const now = Date.now();
    const entries = Object.entries(pollStore)
        .filter(([, value]) => {
            const storedAt = new Date(value?.storedAt || 0).getTime();
            return Number.isFinite(storedAt) && (now - storedAt) <= 7 * 24 * 60 * 60 * 1000;
        })
        .sort(([, a], [, b]) => {
            return new Date(b?.storedAt || 0).getTime() - new Date(a?.storedAt || 0).getTime();
        })
        .slice(0, 500);

    pollStore = Object.fromEntries(entries);
}

function savePollStore() {
    prunePollStore();
    saveJson(POLL_STORE_FILE, pollStore);
}

function rememberPollMessage(messageInfo) {
    if (!messageInfo?.message || !hasPollMessageContent(messageInfo.message)) {
        return;
    }

    const storeKey = buildStoreKey(messageInfo.key);
    if (!storeKey) {
        return;
    }

    pollStore[storeKey] = {
        storedAt: new Date().toISOString(),
        message: messageInfo.message,
    };

    savePollStore();
}

function getStoredPollMessage(key) {
    const storeKey = buildStoreKey(key);
    return storeKey ? pollStore[storeKey]?.message : undefined;
}

function saveRuntimeConfig(nextConfig) {
    runtimeConfig = {
        serverWebhookUrl: String(nextConfig?.serverWebhookUrl || '').trim(),
        serverWebhookKey: String(nextConfig?.serverWebhookKey || '').trim(),
    };

    saveJson(CONFIG_FILE, runtimeConfig);
}

function clearAuthState() {
    fs.rmSync(AUTH_DIR, { recursive: true, force: true });
}

function clearReconnectTimer() {
    if (reconnectTimer) {
        clearTimeout(reconnectTimer);
        reconnectTimer = null;
    }
}

function scheduleReconnect(delayMs = 5000) {
    if (manualDisconnect) {
        return;
    }

    clearReconnectTimer();
    reconnectTimer = setTimeout(() => {
        reconnectTimer = null;
        ensureSocketStarted(true).catch(() => {});
    }, delayMs);
}

function extractMessageBody(message) {
    return String(
        message?.conversation
        || message?.extendedTextMessage?.text
        || message?.buttonsResponseMessage?.selectedDisplayText
        || message?.buttonsResponseMessage?.selectedButtonId
        || message?.templateButtonReplyMessage?.selectedDisplayText
        || message?.templateButtonReplyMessage?.selectedId
        || message?.listResponseMessage?.title
        || message?.listResponseMessage?.singleSelectReply?.title
        || message?.listResponseMessage?.singleSelectReply?.selectedRowId
        || message?.imageMessage?.caption
        || message?.videoMessage?.caption
        || message?.documentMessage?.caption
        || ''
    ).trim();
}

function extractIncomingMessage(messageInfo) {
    const messageType = Object.keys(messageInfo?.message || {})[0] || null;
    const buttonsResponse = messageInfo?.message?.buttonsResponseMessage || null;
    const templateButtonReply = messageInfo?.message?.templateButtonReplyMessage || null;
    const listResponse = messageInfo?.message?.listResponseMessage || null;

    return {
        from: jidToPhone(messageInfo?.key?.remoteJid),
        body: extractMessageBody(messageInfo?.message),
        message_id: messageInfo?.key?.id || null,
        type: messageType,
        selected_button_id: buttonsResponse?.selectedButtonId || templateButtonReply?.selectedId || null,
        selected_button_text: buttonsResponse?.selectedDisplayText || templateButtonReply?.selectedDisplayText || null,
        selected_row_id: listResponse?.singleSelectReply?.selectedRowId || null,
        selected_row_title: listResponse?.title || listResponse?.singleSelectReply?.title || null,
        raw: {
            key: messageInfo?.key || null,
            messageType,
        },
    };
}

function postJson(urlString, payload, headers = {}) {
    return new Promise((resolve, reject) => {
        const target = new URL(urlString);
        const body = Buffer.from(JSON.stringify(payload));
        const transport = target.protocol === 'https:' ? https : http;

        const request = transport.request({
            protocol: target.protocol,
            hostname: target.hostname,
            port: target.port || (target.protocol === 'https:' ? 443 : 80),
            path: target.pathname + target.search,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': body.length,
                ...headers,
            },
        }, (response) => {
            let data = '';

            response.on('data', (chunk) => {
                data += chunk;
            });

            response.on('end', () => {
                let parsed = null;

                try {
                    parsed = data ? JSON.parse(data) : null;
                } catch (error) {
                    parsed = { raw: data };
                }

                resolve({
                    ok: response.statusCode >= 200 && response.statusCode < 300,
                    status: response.statusCode,
                    body: parsed,
                });
            });
        });

        request.on('error', reject);
        request.write(body);
        request.end();
    });
}

async function forwardIncomingToServer(incoming, chatId) {
    if (!runtimeConfig.serverWebhookUrl) {
        return;
    }

    try {
        const response = await postJson(
            runtimeConfig.serverWebhookUrl,
            incoming,
            { 'X-Connectra-Api-Key': runtimeConfig.serverWebhookKey || '' },
        );

        if (!response.ok) {
            logMessage('incoming_error', incoming.from, 'Webhook error ' + response.status, response.body);
            return;
        }

        const replyMessage = String(response.body?.reply?.message || '').trim();
        if (!replyMessage || !sock || status !== 'connected' || !chatId) {
            return;
        }

        const result = await sock.sendMessage(chatId, { text: replyMessage });
        logMessage('outgoing', jidToPhone(chatId) || chatId, replyMessage, result?.key?.id || 'auto-reply');
    } catch (error) {
        logMessage('incoming_error', incoming.from, 'Webhook request failed', error.message || String(error));
    }
}

function buildOutgoingContent(payload) {
    const message = String(payload?.message || '').trim();
    const interactive = payload?.interactive || null;
    const mediaUrl = String(payload?.mediaUrl || '').trim();
    const mediaType = String(payload?.mediaType || 'image').trim().toLowerCase();

    if (mediaUrl) {
        const type = mediaType === 'video' ? 'video' : 'image';
        const content = { [type]: { url: mediaUrl } };
        if (message) content.caption = message;
        return { type: 'media', preview: message || mediaUrl, content };
    }

    if (interactive?.type === 'poll') {
        const values = (Array.isArray(interactive.options) ? interactive.options : [])
            .map((option) => String(option || '').trim())
            .filter(Boolean);

        if (values.length >= 2) {
            return {
                type: 'poll',
                preview: interactive.name || message,
                content: {
                    poll: {
                        name: String(interactive.name || message || 'Rate your visit').trim(),
                        values,
                        selectableCount: interactive.allowMultipleAnswers ? values.length : 1,
                    },
                },
            };
        }
    }

    return {
        type: 'text',
        preview: message,
        content: {
            text: message,
        },
    };
}

function wait(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function waitForQrOrConnection(timeoutMs = 25000) {
    let waited = 0;

    while (!currentQR && status !== 'connected' && status !== 'launch_failed' && waited < timeoutMs) {
        await wait(500);
        waited += 500;
    }
}

async function disconnectSocket(timeoutMs = 5000) {
    const activeSock = sock;

    sock = null;
    status = 'disconnected';
    currentQR = null;
    myPhone = null;
    starting = false;
    lastError = null;

    if (activeSock) {
        try {
            await Promise.race([
                activeSock.logout(),
                wait(timeoutMs),
            ]);
        } catch (error) {
        }

        try {
            activeSock.ws?.close();
        } catch (error) {
        }

        try {
            if (typeof activeSock.end === 'function') {
                activeSock.end(new Error('Manual disconnect'));
            }
        } catch (error) {
        }
    }

    clearAuthState();
}

async function ensureSocketStarted(force = false) {
    if (starting) {
        return;
    }

    if (!force && sock && (status === 'connected' || status === 'qr_ready')) {
        return;
    }

    manualDisconnect = false;
    clearReconnectTimer();
    starting = true;
    status = 'connecting';
    currentQR = null;
    myPhone = null;
    lastError = null;

    try {
        fs.mkdirSync(AUTH_DIR, { recursive: true });

        const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
        const { version } = await fetchLatestBaileysVersion();

        const nextSock = makeWASocket({
            version,
            auth: {
                creds: state.creds,
                keys: makeCacheableSignalKeyStore(state.keys, logger),
            },
            logger,
            printQRInTerminal: false,
            browser: ['Connectra Admin', 'Chrome', '120.0.0'],
            markOnlineOnConnect: false,
            syncFullHistory: false,
            connectTimeoutMs: 60000,
            keepAliveIntervalMs: 10000,
            retryRequestDelayMs: 250,
            getMessage: async (key) => getStoredPollMessage(key),
        });

        sock = nextSock;

        nextSock.ev.on('creds.update', saveCreds);

        nextSock.ev.on('connection.update', async ({ connection, lastDisconnect, qr }) => {
            if (qr) {
                status = 'qr_ready';
                currentQR = await QRCode.toDataURL(qr);
                lastError = null;
                console.log('[WA] QR ready');
            }

            if (connection === 'open') {
                status = 'connected';
                currentQR = null;
                myPhone = jidToPhone(nextSock.user?.id);
                starting = false;
                lastError = null;
                console.log('[WA] Connected as', myPhone || 'unknown');
                return;
            }

            if (connection === 'close') {
                const code = lastDisconnect?.error?.output?.statusCode
                    ?? lastDisconnect?.error?.data?.statusCode
                    ?? null;

                status = 'disconnected';
                currentQR = null;
                myPhone = null;
                starting = false;
                sock = null;
                lastError = lastDisconnect?.error?.message || null;

                console.log('[WA] Connection closed', code || 'unknown');

                if (manualDisconnect) {
                    return;
                }

                if (code === DisconnectReason.loggedOut || code === DisconnectReason.badSession) {
                    clearAuthState();
                    return;
                }

                scheduleReconnect(5000);
            }
        });

        nextSock.ev.on('messages.upsert', async ({ messages }) => {
            for (const messageInfo of messages) {
                if (!messageInfo?.message || messageInfo?.key?.fromMe) {
                    continue;
                }

                const incoming = extractIncomingMessage(messageInfo);
                if (!incoming.from || !incoming.body) {
                    continue;
                }

                logMessage('incoming', incoming.from, incoming.body);
                await forwardIncomingToServer(
                    incoming,
                    messageInfo?.key?.remoteJid || normalizeChatId(incoming.from),
                );
            }
        });

        nextSock.ev.on('messages.update', async (updates) => {
            for (const { key, update } of updates) {
                if (!key || !update?.pollUpdates?.length) {
                    continue;
                }

                const pollMessage = getStoredPollMessage(key);
                if (!pollMessage) {
                    continue;
                }

                for (const pollUpdate of update.pollUpdates) {
                    const aggregated = getAggregateVotesInPollMessage(
                        { message: pollMessage, pollUpdates: [pollUpdate] },
                        nextSock.user?.id,
                    );

                    const selectedOptions = aggregated
                        .filter((option) => Array.isArray(option.voters) && option.voters.length > 0)
                        .map((option) => ({
                            name: option.name,
                            voters: option.voters,
                        }));

                    const selectedOption = selectedOptions[0]?.name || '';
                    if (!selectedOption) {
                        continue;
                    }

                    const voterJid = pollUpdate?.pollUpdateMessageKey?.participant
                        || pollUpdate?.pollUpdateMessageKey?.remoteJid
                        || key.remoteJid
                        || '';

                    const from = jidToPhone(voterJid);
                    if (!from) {
                        continue;
                    }

                    const incoming = {
                        from,
                        body: selectedOption,
                        message_id: pollUpdate?.pollUpdateMessageKey?.id || (key.id ? 'poll:' + key.id + ':' + from : null),
                        type: 'poll_vote',
                        selected_poll_option: selectedOption,
                        selected_poll_options: selectedOptions,
                        raw: {
                            key,
                            pollUpdateMessageKey: pollUpdate?.pollUpdateMessageKey || null,
                        },
                    };

                    logMessage('incoming', from, 'poll vote: ' + selectedOption);
                    await forwardIncomingToServer(incoming, key.remoteJid || normalizeChatId(from));
                }
            }
        });
    } catch (error) {
        starting = false;
        sock = null;
        status = 'launch_failed';
        currentQR = null;
        myPhone = null;
        lastError = error.message || String(error);
        console.error('[WA] Failed to start socket:', lastError);
        throw error;
    }
}

app.get('/', (req, res) => {
    res.json({
        service: 'connectra-whatsapp-web',
        ...statusPayload(),
        webhookConfigured: Boolean(runtimeConfig.serverWebhookUrl),
    });
});

app.get('/whatsapp/status', requireApiKey, async (req, res) => {
    if (!sock && fs.existsSync(AUTH_DIR) && !manualDisconnect) {
        ensureSocketStarted().catch(() => {});
    }

    res.json(statusPayload(true));
});

app.post('/whatsapp/connect', requireApiKey, async (req, res) => {
    try {
        if (status !== 'connected' && (!sock || status === 'disconnected' || status === 'launch_failed')) {
            await ensureSocketStarted(true);
        }
    } catch (error) {
        return res.status(503).json({
            ...statusPayload(true),
            error: lastError || 'Failed to initialize WhatsApp Web.',
        });
    }

    await waitForQrOrConnection(10000);

    if (status === 'connected' || status === 'qr_ready') {
        return res.json(statusPayload(true));
    }

    return res.status(202).json({
        ...statusPayload(true),
        message: 'Connection is starting. Retry in a moment.',
    });
});

app.get('/whatsapp/qr', requireApiKey, async (req, res) => {
    if (status !== 'connected' && (!sock || status === 'disconnected' || status === 'launch_failed')) {
        try {
            await ensureSocketStarted(true);
        } catch (error) {
            return res.status(503).json({
                error: lastError || 'Failed to initialize WhatsApp Web.',
                status,
            });
        }
    }

    await waitForQrOrConnection();

    if (status === 'connected') {
        return res.json(statusPayload(true));
    }

    if (!currentQR) {
        return res.status(202).json({
            ...statusPayload(true),
            message: 'QR not ready yet, retry in a moment.',
        });
    }

    res.json(statusPayload(true));
});

app.get('/whatsapp/qr/image', requireApiKey, async (req, res) => {
    if (!currentQR) {
        if (status !== 'connected' && (!sock || status === 'disconnected' || status === 'launch_failed')) {
            try {
                await ensureSocketStarted(true);
            } catch (error) {
                return res.status(503).json({
                    error: lastError || 'Failed to initialize WhatsApp Web.',
                    status,
                });
            }
        }

        await waitForQrOrConnection(10000);
    }

    if (!currentQR) {
        return res.status(404).json({
            error: 'QR not ready yet.',
            status,
        });
    }

    const base64 = currentQR.replace(/^data:image\/png;base64,/, '');
    res.setHeader('Cache-Control', 'no-store');
    res.type('png').send(Buffer.from(base64, 'base64'));
});

app.post('/whatsapp/configure', requireApiKey, (req, res) => {
    const serverWebhookUrl = String(req.body?.serverWebhookUrl || '').trim();
    const serverWebhookKey = String(req.body?.serverWebhookKey || '').trim();

    if (!serverWebhookUrl) {
        return res.status(422).json({
            success: false,
            error: 'Missing serverWebhookUrl',
        });
    }

    saveRuntimeConfig({ serverWebhookUrl, serverWebhookKey });

    res.json({
        success: true,
        config: runtimeConfig,
    });
});

// Check whether a number is actually registered on WhatsApp. Messaging numbers that
// do not exist is a fast route to getting the line blocked, so callers should filter first.
app.post('/whatsapp/check', requireApiKey, async (req, res) => {
    if (status !== 'connected' || !sock) {
        return res.status(503).json({
            error: 'WhatsApp Web is not connected.',
            status,
        });
    }

    const digits = String(req.body?.to || '').replace(/[^0-9]/g, '');
    if (!digits) {
        return res.status(400).json({ error: 'Missing or invalid "to".' });
    }

    try {
        const [result] = await sock.onWhatsApp(digits + '@s.whatsapp.net');

        res.json({
            success: true,
            to: digits,
            exists: Boolean(result?.exists),
            jid: result?.jid || null,
        });
    } catch (error) {
        res.status(500).json({
            error: error.message || 'Failed to check WhatsApp number.',
        });
    }
});

app.post('/whatsapp/send', requireApiKey, sendLimiter, async (req, res) => {
    if (status !== 'connected' || !sock) {
        return res.status(503).json({
            error: 'WhatsApp Web is not connected.',
            status,
        });
    }

    const to = String(req.body?.to || '').trim();
    const jid = normalizeChatId(to);
    if (!jid) {
        return res.status(400).json({ error: 'Missing or invalid "to".' });
    }

    const outgoing = buildOutgoingContent(req.body);
    if (outgoing.type === 'text' && !outgoing.preview) {
        return res.status(400).json({ error: 'Missing "message".' });
    }

    try {
        const result = await sock.sendMessage(jid, outgoing.content);
        if (outgoing.type === 'poll') {
            rememberPollMessage(result);
        }

        logMessage('outgoing', jid, outgoing.preview, result?.key?.id || null);

        res.json({
            success: true,
            messageId: result?.key?.id || null,
            to: jid,
            type: outgoing.type,
        });
    } catch (error) {
        logMessage('outgoing_error', jid, outgoing.preview, error.message || String(error));
        res.status(500).json({
            error: error.message || 'Failed to send WhatsApp message.',
        });
    }
});

app.post('/whatsapp/disconnect', requireApiKey, async (req, res) => {
    manualDisconnect = true;
    clearReconnectTimer();
    await disconnectSocket();

    res.json({
        success: true,
        ...statusPayload(),
    });
});

app.get('/whatsapp/logs', requireApiKey, (req, res) => {
    if (!fs.existsSync(LOG_FILE)) {
        return res.json([]);
    }

    const lines = fs.readFileSync(LOG_FILE, 'utf8')
        .split('\n')
        .filter(Boolean);

    const logs = lines
        .slice(-50)
        .map((line) => {
            try {
                return JSON.parse(line);
            } catch (error) {
                return null;
            }
        })
        .filter(Boolean)
        .reverse();

    res.json(logs);
});

app.listen(PORT, () => {
    console.log('[WA Service] Running on http://127.0.0.1:' + PORT);
    console.log('[WA Service] For internal testing only.');

    if (fs.existsSync(AUTH_DIR) && !manualDisconnect) {
        ensureSocketStarted().catch(() => {});
    }
});
