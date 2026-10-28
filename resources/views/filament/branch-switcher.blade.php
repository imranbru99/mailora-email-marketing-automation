@auth
<form method="POST" action="{{ route('branch.switch') }}" class="flex items-center gap-2 px-3">@csrf
<label for="branch-switch" class="sr-only">Branch</label><select id="branch-switch" name="branch_id" onchange="this.form.submit()" class="rounded-lg border-gray-200 bg-transparent text-sm dark:border-gray-700" @disabled(auth()->user()->branch_id)>
@if(!auth()->user()->branch_id)<option value="">All branches</option>@endif
@foreach(\App\Models\Branch::where('business_id',auth()->user()->business_id)->get() as $branch)<option value="{{ $branch->id }}" @selected(app(\App\Services\TenantContext::class)->branchId===$branch->id)>{{ $branch->name }}</option>@endforeach
</select></form>@endauth