<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sales Order Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Create Sales Order Form -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4">New Sales Order</h3>
                    <form action="{{ route('sales.orders.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Customer</label>
                            <select name="erp_customer_id" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                                @forelse ($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->company ?? 'Direct' }})</option>
                                @empty
                                    <option value="">No customers configured</option>
                                @endforelse
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Order Date</label>
                            <input type="date" name="order_date" value="{{ date('Y-m-d') }}" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                        </div>
                        
                        <div class="border-t pt-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Item to Sell</label>
                            <div class="space-y-2">
                                <select name="items[0][erp_product_id]" class="block w-full border-gray-300 rounded text-sm" required>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} (Stock: {{ $p->current_stock }})</option>
                                    @endforeach
                                </select>
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="number" name="items[0][quantity]" placeholder="Qty" value="1" min="1" class="border-gray-300 rounded text-sm" required>
                                    <input type="number" step="0.01" name="items[0][unit_price]" placeholder="Price ($)" value="30.00" min="0" class="border-gray-300 rounded text-sm" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded text-sm">+ Create Sales Order</button>
                    </form>
                </div>

                <!-- Sales Orders Table -->
                <div class="col-span-2 bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4">Sales Orders Workflow</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                                <tr>
                                    <th class="px-4 py-2 text-left">SO #</th>
                                    <th class="px-4 py-2 text-left">Customer</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                    <th class="px-4 py-2 text-center">Status</th>
                                    <th class="px-4 py-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($orders as $order)
                                    <tr>
                                        <td class="px-4 py-3 font-mono font-bold text-gray-900">{{ $order->so_number }}</td>
                                        <td class="px-4 py-3">{{ $order->customer->name }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-gray-900">${{ number_format($order->total_cents / 100, 2) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-0.5 rounded text-xs font-bold {{ $order->status === 'fulfilled' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if ($order->status !== 'fulfilled')
                                                <form action="{{ route('sales.orders.fulfill', $order) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded">Fulfill & Ship &rarr;</button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-500">Shipped</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-3 text-center text-gray-500">No sales orders created yet.</td>
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
