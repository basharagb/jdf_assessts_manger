<?php

return [
    'accepted' => 'يجب الموافقة على :attribute.',
    'after' => 'يجب أن يكون :attribute بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute في :date أو بعده.',
    'date' => 'يرجى إدخال تاريخ صحيح في :attribute.',
    'date_format' => 'يجب أن يطابق :attribute التنسيق :format.',
    'email' => 'يرجى إدخال بريد إلكتروني صحيح.',
    'image' => 'يجب أن يكون :attribute صورة.',
    'max' => [
        'numeric' => 'يجب ألا تزيد قيمة :attribute عن :max.',
        'file' => 'يجب ألا يتجاوز حجم :attribute :max كيلوبايت.',
        'string' => 'يجب ألا يزيد :attribute عن :max حرفاً.',
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
    ],
    'mimes' => 'يجب أن يكون نوع ملف :attribute أحد الأنواع التالية: :values.',
    'min' => [
        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
        'file' => 'يجب ألا يقل حجم :attribute عن :min كيلوبايت.',
        'string' => 'يجب ألا يقل :attribute عن :min أحرف.',
        'array' => 'يجب ألا يحتوي :attribute على أقل من :min عناصر.',
    ],
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'attributes' => [
        'name' => 'اسم السوق', 'area' => 'المساحة', 'lessor' => 'الجهة المؤجرة',
        'monthly_rent' => 'الإيجار الشهري', 'lease_start_date' => 'تاريخ بداية الإيجار',
        'lease_end_date' => 'تاريخ نهاية العقد', 'image' => 'صورة السوق',
        'description' => 'الملاحظات', 'email' => 'البريد الإلكتروني', 'password' => 'كلمة المرور',
    ],
];
