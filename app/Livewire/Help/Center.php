<?php

declare(strict_types=1);

namespace App\Livewire\Help;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Help Center — searchable, grouped FAQ accordion + support contact links.
 * The source loads /api/provider/faqs (bilingual, grouped, with a fallback);
 * this clone ships the fallback set inline and resolves q/a by locale.
 */
#[Layout('components.layouts.app')]
#[Title('Help Center — Waqty')]
class Center extends Component
{
    public string $search = '';

    /** Support contacts (config-driven). */
    public function supportEmail(): string
    {
        return (string) config('waqty.support.email');
    }

    public function supportWhatsapp(): string
    {
        return (string) config('waqty.support.whatsapp');
    }

    /**
     * FAQ groups filtered by the search term (matches question or answer).
     *
     * @return array<int, array{category:string, items:array<int, array{q:string, a:string}>}>
     */
    #[Computed]
    public function groups(): array
    {
        $ar = app()->getLocale() === 'ar';
        $q = trim(mb_strtolower($this->search));

        $out = [];
        foreach ($this->faqs() as $group) {
            $category = $ar ? $group['cat_ar'] : $group['cat_en'];
            $items = [];
            foreach ($group['items'] as $item) {
                $question = $ar ? $item['q_ar'] : $item['q_en'];
                $answer = $ar ? $item['a_ar'] : $item['a_en'];
                if ($q === '' || str_contains(mb_strtolower($question.' '.$answer), $q)) {
                    $items[] = ['q' => $question, 'a' => $answer];
                }
            }
            if ($items !== []) {
                $out[] = ['category' => $category, 'items' => $items];
            }
        }

        return $out;
    }

    public function render()
    {
        return view('livewire.help.center');
    }

    /** @return array<int, array<string, mixed>> */
    private function faqs(): array
    {
        return [
            [
                'cat_en' => 'Getting started', 'cat_ar' => 'البدء',
                'items' => [
                    ['q_en' => 'How do I add my first service?', 'q_ar' => 'كيف أضيف أول خدمة؟', 'a_en' => 'Go to Settings › Services and click “Add Service”. Enter a name, duration, and price, then save.', 'a_ar' => 'انتقل إلى الإعدادات ‹ الخدمات واضغط «إضافة خدمة». أدخل الاسم والمدة والسعر ثم احفظ.'],
                    ['q_en' => 'How do I invite my team?', 'q_ar' => 'كيف أدعو فريقي؟', 'a_en' => 'Open Employees and use “Add Employee” to create an account; they receive their login by email.', 'a_ar' => 'افتح الموظفين واستخدم «إضافة موظف» لإنشاء حساب؛ سيستلمون بيانات الدخول عبر البريد.'],
                ],
            ],
            [
                'cat_en' => 'Bookings', 'cat_ar' => 'الحجوزات',
                'items' => [
                    ['q_en' => 'Can a client book online?', 'q_ar' => 'هل يمكن للعميل الحجز عبر الإنترنت؟', 'a_en' => 'Yes — enable online booking in Settings › General. Your public booking page updates instantly.', 'a_ar' => 'نعم — فعّل الحجز عبر الإنترنت من الإعدادات ‹ عام. تُحدَّث صفحة الحجز العامة فورًا.'],
                    ['q_en' => 'How do I cancel a booking?', 'q_ar' => 'كيف ألغي حجزًا؟', 'a_en' => 'Open the booking and choose “Cancel”. You can add an optional reason that is logged in the activity timeline.', 'a_ar' => 'افتح الحجز واختر «إلغاء». يمكنك إضافة سبب اختياري يُسجَّل في سجل النشاط.'],
                ],
            ],
            [
                'cat_en' => 'Payments & money', 'cat_ar' => 'المدفوعات والمالية',
                'items' => [
                    ['q_en' => 'How is VAT calculated?', 'q_ar' => 'كيف تُحتسب ضريبة القيمة المضافة؟', 'a_en' => 'VAT is applied at 14% and shown on every invoice. Adjust the rate in Settings › Invoice.', 'a_ar' => 'تُطبَّق ضريبة القيمة المضافة بنسبة 14٪ وتظهر في كل فاتورة. عدّل النسبة من الإعدادات ‹ الفاتورة.'],
                    ['q_en' => 'Can I issue a refund?', 'q_ar' => 'هل يمكنني إصدار استرداد؟', 'a_en' => 'Yes — go to Returns and start a cash refund, down-payment cancellation, or petty-cash refund.', 'a_ar' => 'نعم — انتقل إلى المرتجعات وابدأ استرداد نقدي أو إلغاء دفعة مقدمة أو استرداد نثرية.'],
                ],
            ],
            [
                'cat_en' => 'Account & security', 'cat_ar' => 'الحساب والأمان',
                'items' => [
                    ['q_en' => 'How do I reset my password?', 'q_ar' => 'كيف أعيد تعيين كلمة المرور؟', 'a_en' => 'Use “Forgot password” on the login screen. We send a 6-digit code to your email to verify.', 'a_ar' => 'استخدم «نسيت كلمة المرور» في شاشة الدخول. نرسل رمزًا من 6 أرقام إلى بريدك للتحقق.'],
                    ['q_en' => 'Can I control what staff can see?', 'q_ar' => 'هل يمكنني التحكم فيما يراه الموظفون؟', 'a_en' => 'Yes — Settings › Roles lets you set granular view/create/edit/delete permissions per module.', 'a_ar' => 'نعم — تتيح لك الإعدادات ‹ الأدوار ضبط صلاحيات عرض/إنشاء/تعديل/حذف مفصلة لكل وحدة.'],
                ],
            ],
        ];
    }
}
