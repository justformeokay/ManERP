{{-- Data Table Component --}}
{{-- Usage: @include('components.data-table', ['headers' => [...], 'rows' => [...], 'actions' => true]) --}}
<div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
    @isset($tableTitle)
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900">{{ $tableTitle }}</h3>
            @isset($tableAction)
                {!! $tableAction !!}
            @endisset
        </div>
    @endisset

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50/50">
                <tr>
                    @foreach($headers as $header)
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ $header }}
                        </th>
                    @endforeach
                    @if($actions ?? false)
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Actions
                        </th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        @foreach($row['cells'] as $cell)
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {!! $cell !!}
                            </td>
                        @endforeach
                        @if($actions ?? false)
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm space-x-2">
                                @isset($row['editUrl'])
                                    <a href="{{ $row['editUrl'] }}" class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 transition">Edit</a>
                                @endisset
                                @isset($row['deleteUrl'])
                                    <button
                                        onclick="if(confirm('Are you sure?')) document.getElementById('delete-{{ $loop->index }}').submit()"
                                        class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 transition"
                                    >Delete</button>
                                    <form id="delete-{{ $loop->index }}" method="POST" action="{{ $row['deleteUrl'] }}" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                @endisset
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($headers) + (($actions ?? false) ? 1 : 0) }}" class="px-6 py-12 text-center text-sm text-gray-400">
                            No records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
