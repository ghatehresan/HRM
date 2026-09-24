@php($editing = isset($role) && $role->exists)
@php($selected = old('permissions', $selectedPerms ?? []))
@php($selected = is_array($selected) ? $selected : [])
<x-form.input name="name" label="نام نقش" required :value="$editing ? $role->name : ''" />
@if($editing && $role->slug === 'super-admin')
    <x-form.input name="slug" label="شناسه" ltr :value="$role->slug" disabled hint="شناسهٔ نقش super-admin قابل تغییر نیست." />
@else
    <x-form.input name="slug" label="شناسه (لاتین)" required ltr :value="$editing ? $role->slug : ''" hint="مثل hr-manager؛ فقط حروف لاتین، عدد، خط تیره و آندرلاین." />
@endif
<x-form.input name="description" label="توضیح" :value="$editing ? ($role->description ?? '') : ''" />
<div class="mb-4">
    <span class="field-label">مجوزها</span>
    @foreach($grouped as $group => $perms)
        <div class="mb-3 rounded-lg border border-line p-3">
            <div class="font-en mb-2 text-[12.5px] font-bold text-steel" dir="ltr">{{ $group }}</div>
            @foreach($perms as $perm)
                <x-form.checkbox name="permissions[]" :value="$perm->name" :label="$perm->name.($perm->description ? ' — '.$perm->description : '')" :checked="in_array($perm->name, $selected)" />
            @endforeach
        </div>
    @endforeach
</div>
