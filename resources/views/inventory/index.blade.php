<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inventory & Warehouse Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Add Product Form -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4">Register New Item</h3>
                    <form action="{{ route('inventory.products.store') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Item Name</label>
                            <input type="text" name="name" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">SKU Code</label>
                            <input type="text" name="sku" class="mt-1 block w-full border-gray-300 rounded text-sm uppercase" placeholder="e.g. ELEC-001" required>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Cost ($)</label>
                                <input type="number" step="0.01" name="cost_price" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Selling ($)</label>
                                <input type="number" step="0.01" name="unit_price" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Min Threshold</label>
                                <input type="number" name="min_stock_threshold" value="10" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Initial Stock</label>
                                <input type="number" name="initial_stock" value="0" class="mt-1 block w-full border-gray-300 rounded text-sm">
                            </div>
                        </div>
                        <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded text-sm">+ Save Product</button>
                    </form>
                </div>

                <!-- Products Catalog Table -->
                <div class="col-span-2 bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4">Live Product Stock</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                                <tr>
                                    <th class="px-4 py-2 text-left">SKU</th>
                                    <th class="px-4 py-2 text-left">Item Name</th>
                                    <th class="px-4 py-2 text-right">Cost</th>
                                    <th class="px-4 py-2 text-right">Selling</th>
                                    <th class="px-4 py-2 text-center">Stock</th>
                                    <th class="px-4 py-2 text-right">Quick Adjust</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($products as $p)
                                    <tr>
                                        <td class="px-4 py-3 font-mono font-bold text-gray-900">{{ $p->sku }}</td>
                                        <td class="px-4 py-3">{{ $p->name }}</td>
                                        <td class="px-4 py-3 text-right text-gray-600">${{ number_format($p->cost_price_cents / 100, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-900">${{ number_format($p->unit_price_cents / 100, 2) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-0.5 rounded text-xs font-bold {{ $p->isLowStock() ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                {{ $p->current_stock }} {{ $p->unit_of_measure }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <form action="{{ route('inventory.products.adjust', $p) }}" method="POST" class="inline-flex items-center space-x-1">
                                                @csrf
                                                <input type="hidden" name="type" value="in">
                                                <input type="hidden" name="notes" value="Quick restock">
                                                <input type="number" name="quantity" value="5" min="1" class="w-16 p-1 text-xs border-gray-300 rounded text-center">
                                                <button type="submit" class="p-1 px-2 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-bold">+ In</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-3 text-center text-gray-500">No inventory registered.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
