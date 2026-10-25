<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Enterprise Resource Planning (ERP) Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Metric Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="text-sm font-medium text-gray-500">Products in Catalog</div>
                    <div class="mt-2 text-3xl font-bold text-gray-900">{{ $metrics['total_products'] }}</div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="text-sm font-medium text-gray-500">Low Stock Warnings</div>
                    <div class="mt-2 text-3xl font-bold {{ $metrics['low_stock_count'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $metrics['low_stock_count'] }}
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="text-sm font-medium text-gray-500">Pending Orders (PO / SO)</div>
                    <div class="mt-2 text-3xl font-bold text-indigo-600">{{ $metrics['pending_pos_count'] }} / {{ $metrics['open_sales_count'] }}</div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="text-sm font-medium text-gray-500">Liquid Cash Balance</div>
                    <div class="mt-2 text-3xl font-bold text-emerald-600">${{ number_format($metrics['net_cash_balance_cents'] / 100, 2) }}</div>
                </div>
            </div>

            <!-- Low Stock Alerts -->
            @if ($lowStockProducts->count() > 0)
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-red-800 font-semibold text-sm">Action Required: {{ $lowStockProducts->count() }} items reached minimum stock threshold!</span>
                        </div>
                        <a href="{{ route('purchasing.index') }}" class="text-xs font-semibold uppercase tracking-wider text-red-800 hover:underline">Create Purchase Order &rarr;</a>
                    </div>
                    <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-red-700">
                        @foreach ($lowStockProducts as $lp)
                            <div class="bg-white p-2 rounded border border-red-200">
                                <strong>{{ $lp->name }}</strong> ({{ $lp->sku }}): Stock {{ $lp->current_stock }} / Min {{ $lp->min_stock_threshold }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Two-column status grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Recent Purchase Orders -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-900">Open Purchase Orders</h3>
                        <a href="{{ route('purchasing.index') }}" class="text-sm text-indigo-600 hover:underline">View All</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($pendingPOs->take(5) as $po)
                            <div class="py-3 flex justify-between items-center text-sm">
                                <div>
                                    <span class="font-semibold text-gray-900">{{ $po->po_number }}</span>
                                    <span class="text-gray-500"> &bull; {{ $po->supplier->name }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-gray-900">${{ number_format($po->total_cents / 100, 2) }}</div>
                                    <span class="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-800 uppercase font-semibold">{{ $po->status }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 py-3">No pending purchase orders.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Sales Orders -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-900">Open Sales Orders</h3>
                        <a href="{{ route('sales.index') }}" class="text-sm text-indigo-600 hover:underline">View All</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($openSalesOrders->take(5) as $so)
                            <div class="py-3 flex justify-between items-center text-sm">
                                <div>
                                    <span class="font-semibold text-gray-900">{{ $so->so_number }}</span>
                                    <span class="text-gray-500"> &bull; {{ $so->customer->name }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-gray-900">${{ number_format($so->total_cents / 100, 2) }}</div>
                                    <span class="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-800 uppercase font-semibold">{{ $so->status }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 py-3">No active sales orders.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="font-bold text-gray-900 mb-4">System Audit Trail</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-2 text-left">Time</th>
                                <th class="px-4 py-2 text-left">User</th>
                                <th class="px-4 py-2 text-left">Module</th>
                                <th class="px-4 py-2 text-left">Action</th>
                                <th class="px-4 py-2 text-left">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($recentAudits as $audit)
                                <tr>
                                    <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ $audit->created_at->diffForHumans() }}</td>
                                    <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap">{{ $audit->user->name ?? 'System' }}</td>
                                    <td class="px-4 py-2"><span class="px-2 py-0.5 bg-gray-100 rounded text-xs font-semibold">{{ $audit->module }}</span></td>
                                    <td class="px-4 py-2 font-mono text-xs text-indigo-700">{{ $audit->action }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $audit->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-2 text-gray-500 text-center">No audit activity logged.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
