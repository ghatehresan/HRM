@php($editing = isset($user) && $user->exists)
@php($selected = old('roles', $selectedRoles ?? []))
@php($selected = is_array($selected) ? $selected : [])
<x-form.input name="name" label="نام" required :value="$editing ? $user->name : ''" />
<x-form.input name="email" label="ایمیل" type="email" required ltr :value="$editing ? $user->email : ''" />
<x-form.input name="password" label="رمز عبور" type="password" :required="!$editing" :hint="$editing ? 'خالی بگذارید تا تغییر نکند.' : 'حداقل ۱۲ کاراکتر.'" autocomplete="new-password" />
<div class="mb-4">
    <span class="field-label">نقش‌ها</span>
    @foreach($roles as $role)
        <x-form.checkbox name="roles[]" :value="$role->slug" :label="$role->name.' ('.$role->slug.')'" :checked="in_array($role->slug, $selected)" />
    @endforeach
</div>
<div class="grid grid-cols-1 gap-4 tablet:grid-cols-2">
    <x-form.select name="is_active" label="وضعیت" :options="['1' => 'فعال', '0' => 'غیرفعال']" :value="$editing ? (string) (int) $user->is_active : '1'" />
    <x-form.select name="mfa_enforced" label="الزام تأیید دومرحله‌ای" :options="['1' => 'بله', '0' => 'خیر']" :value="$editing ? (string) (int) $user->mfa_enforced : '0'" />
</div>
