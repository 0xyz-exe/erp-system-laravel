<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Financial Accounting & Ledger') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Accounts Balance Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @foreach ($accounts as $acc)
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $acc->code }} &bull; {{ $acc->type }}</div>
                        <div class="text-md font-bold text-gray-900 mt-1">{{ $acc->name }}</div>
                        <div class="text-xl font-extrabold text-indigo-700 mt-2">${{ number_format($acc->balance_cents / 100, 2) }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Record Transaction Form -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4">Record Transaction</h3>
                    <form action="{{ route('finance.transactions.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Account</label>
                            <select name="financial_account_id" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                                @foreach ($accounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }} ({{ $a->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Type</label>
                                <select name="type" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                                    <option value="income">Income (+)</option>
                                    <option value="expense">Expense (-)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Amount ($)</label>
                                <input type="number" step="0.01" name="amount" placeholder="0.00" min="0.01" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Description</label>
                            <input type="text" name="description" placeholder="Client payment / Office supplies" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Reference</label>
                                <input type="text" name="reference" placeholder="INV-001" class="mt-1 block w-full border-gray-300 rounded text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Date</label>
                                <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                            </div>
                        </div>
                        <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded text-sm">+ Record Entry</button>
                    </form>
                </div>

                <!-- Transaction Journal -->
                <div class="col-span-2 bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4">Transaction Ledger</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                                <tr>
                                    <th class="px-4 py-2 text-left">Date</th>
                                    <th class="px-4 py-2 text-left">Account</th>
                                    <th class="px-4 py-2 text-left">Description</th>
                                    <th class="px-4 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($transactions as $tx)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $tx->transaction_date->format('Y-m-d') }}</td>
                                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $tx->account->name }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $tx->description }}</td>
                                        <td class="px-4 py-3 text-right font-mono font-bold {{ $tx->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $tx->type === 'income' ? '+' : '-' }}${{ number_format($tx->amount_cents / 100, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-center text-gray-500">No transactions recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
