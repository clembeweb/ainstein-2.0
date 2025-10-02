@extends('admin.layout')
@section('content')
<div class="px-4 py-6"><h1 class="text-3xl font-bold mb-6">Create Tenant</h1><div class="bg-white p-6 rounded-lg shadow max-w-2xl"><form method="POST" action="{{ route('admin.tenants.store') }}">@csrf
<div class="mb-4"><label class="block mb-2">Name</label><input type="text" name="name" required class="w-full px-3 py-2 border rounded"></div>
<div class="mb-4"><label class="block mb-2">Subdomain</label><input type="text" name="subdomain" class="w-full px-3 py-2 border rounded"></div>
<div class="mb-4"><label class="block mb-2">Plan</label><select name="plan_type" required class="w-full px-3 py-2 border rounded"><option value="starter">Starter (10K tokens)</option><option value="professional">Professional (50K tokens)</option><option value="enterprise">Enterprise (200K tokens)</option></select></div>
<div class="mb-4"><label class="block mb-2">Status</label><select name="status" required class="w-full px-3 py-2 border rounded"><option value="active">Active</option><option value="trial">Trial</option><option value="suspended">Suspended</option></select></div>
<div class="mb-4"><label class="block mb-2">Tokens Monthly Limit</label><input type="number" name="tokens_monthly_limit" required value="10000" class="w-full px-3 py-2 border rounded"></div>
<div class="flex gap-2"><button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Create</button><a href="{{ route('admin.tenants') }}" class="bg-gray-200 px-4 py-2 rounded">Cancel</a></div>
</form></div></div>
@endsection
