@extends('admin.layout')
@section('content')
<div class="px-4 py-6"><h1 class="text-3xl font-bold mb-6">Settings</h1>
<div class="bg-white p-6 rounded-lg shadow max-w-2xl"><form method="POST" action="{{ route('admin.settings.update') }}">@csrf
<div class="mb-4"><label class="block text-gray-700 mb-2 font-bold">OpenAI API Key</label><input type="text" name="openai_api_key" required class="w-full px-3 py-2 border rounded font-mono" value="{{ $settings['openai_api_key'] ?? '' }}"><p class="text-sm text-gray-500 mt-1">Your OpenAI API key for all tenants</p></div>
<div class="mb-4"><label class="block text-gray-700 mb-2 font-bold">OpenAI Model</label><select name="openai_model" required class="w-full px-3 py-2 border rounded"><option value="gpt-4" {{$settings['openai_model']=='gpt-4'?'selected':''}}>GPT-4</option><option value="gpt-4-turbo" {{$settings['openai_model']=='gpt-4-turbo'?'selected':''}}>GPT-4 Turbo</option><option value="gpt-3.5-turbo" {{$settings['openai_model']=='gpt-3.5-turbo'?'selected':''}}>GPT-3.5 Turbo</option></select></div>
<button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save Settings</button>
</form></div></div>
@endsection
