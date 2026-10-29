<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Claim Form Templates</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-600">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <p class="text-sm text-gray-600">
                        Upload updated DOCX files here. They replace the files in <code>{{ $path }}</code>. A backup is created when replacing an existing file.
                    </p>
                    <p class="text-sm text-gray-600">
                        See the <a href="{{ route('templates.placeholders') }}" class="text-indigo-600 hover:text-indigo-800 underline">placeholder reference</a> for every placeholder the generator supports.
                    </p>

                    <form method="POST" action="{{ route('templates.update') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="space-y-3">
                            @foreach ($templates as $template)
                                <div class="flex flex-col gap-2 rounded border border-gray-200 p-4 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <div class="font-semibold">{{ $template['filename'] }}</div>
                                        <div class="text-sm text-gray-600">
                                            @if ($template['exists'])
                                                {{ number_format($template['size'] / 1024, 1) }} KB • Updated {{ $template['updated_at'] }}
                                            @else
                                                Missing
                                            @endif
                                        </div>
                                    </div>
                                    <input type="file" name="{{ $template['key'] }}" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="block w-full text-sm text-gray-600 file:mr-4 file:rounded file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 md:w-auto" />
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Upload Selected</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-2 text-sm text-gray-600">
                    <p class="font-semibold text-gray-900">Notes</p>
                    <p>Keep placeholder names unchanged if you want auto-filling to work (e.g. <code>&#123;&#123;patient_name&#125;&#125;</code>, <code>&#123;&#123;P_Lastname&#125;&#125;</code>, <code>&#123;&#123;D_FULLNAME&#125;&#125;</code>).</p>
                    <p>After uploading, download a test form to confirm fields populate correctly.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
