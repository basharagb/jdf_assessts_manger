<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';
    protected $description = 'إنشاء حساب مدير للنظام';

    public function handle(): int
    {
        $name = trim((string) $this->ask('اسم المدير'));
        $email = trim((string) $this->ask('البريد الإلكتروني'));
        $password = (string) $this->secret('كلمة المرور (8 أحرف على الأقل)');
        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            $this->error('أدخل اسماً وبريداً صحيحاً وكلمة مرور من 8 أحرف على الأقل.');
            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('يوجد حساب بهذا البريد الإلكتروني بالفعل.');
            return self::FAILURE;
        }

        User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
        $this->info('تم إنشاء حساب المدير. يمكنك تسجيل الدخول الآن.');
        return self::SUCCESS;
    }
}
