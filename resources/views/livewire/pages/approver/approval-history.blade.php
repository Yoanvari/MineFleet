<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\ReservationApproval;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public $statusFilter = 'all';
    public $search = '';
    public $selectedApproval = null;
    public $showDetailModal = false;

    public function mount()
    {
        // Ensure user is approver
        if (Auth::user()->role !== 'approver') {
            abort(403, 'Unauthorized');
        }
    }

    public function with()
    {
        $query = ReservationApproval::with([
            'reservation.vehicle',
            'reservation.driver',
            'reservation.requester',
            'reservation.location'
        ])
        ->where('approver_id', Auth::id())
        ->where('status', '!=', 'pending');

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Apply search filter
        if ($this->search) {
            $query->whereHas('reservation', function ($q) {
                $q->where('reservation_code', 'like', '%' . $this->search . '%')
                  ->orWhereHas('requester', function ($q2) {
                      $q2->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('vehicle', function ($q3) {
                      $q3->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('license_plate', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return [
            'approvalHistory' => $query->orderBy('approved_at', 'desc')->paginate(10),
            'statusCounts' => $this->getStatusCounts()
        ];
    }

    public function getStatusCounts()
    {
        $counts = ReservationApproval::where('approver_id', Auth::id())
            ->where('status', '!=', 'pending')
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'all' => array_sum($counts),
            'approved' => $counts['approved'] ?? 0,
            'rejected' => $counts['rejected'] ?? 0,
        ];
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    public function showDetail($approvalId)
    {
        $this->selectedApproval = ReservationApproval::with([
            'reservation.vehicle',
            'reservation.driver',
            'reservation.requester',
            'reservation.location'
        ])->findOrFail($approvalId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedApproval = null;
    }

    public function getStatusBadgeClass($status)
    {
        return match($status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'completed' => 'bg-blue-100 text-blue-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getVehicleTypeBadge($type)
    {
        return match($type) {
            'passenger' => 'bg-blue-100 text-blue-800',
            'cargo' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
}; ?>

<section class="w-full">
    <flux:main>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-start">
                <div>
                    <flux:heading size="xl">Approval History</flux:heading>
                    <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                        View your approval history and decisions
                    </flux:text>
                </div>
            </div>

            <!-- Statistic Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total -->
                <div class="bg-white dark:bg-zinc-900 shadow rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 flex items-center gap-4">
                    <flux:icon name="clipboard-document-check" class="w-7 h-7 text-zinc-500 dark:text-zinc-300" />
                    <div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Processed</div>
                        <div class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $statusCounts['all'] }}
                        </div>
                    </div>
                </div>

                <!-- Approved -->
                <div class="bg-white dark:bg-zinc-900 shadow rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 flex items-center gap-4">
                    <flux:icon name="check-circle" class="w-7 h-7 text-green-500" />
                    <div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Approved</div>
                        <div class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $statusCounts['approved'] }}
                        </div>
                    </div>
                </div>

                <!-- Rejected -->
                <div class="bg-white dark:bg-zinc-900 shadow rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 flex items-center gap-4">
                    <flux:icon name="x-circle" class="w-7 h-7 text-red-500" />
                    <div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Rejected</div>
                        <div class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $statusCounts['rejected'] }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="p-4 bg-white dark:bg-zinc-900 shadow rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Search -->
                    <flux:field class="md:col-span-3">
                        <flux:label>Search</flux:label>
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by reservation code, requester, or vehicle..."
                            icon="magnifying-glass" />
                    </flux:field>

                    <!-- Status -->
                    <flux:field class="md:col-span-2">
                        <flux:label>Status</flux:label>
                        <flux:select wire:model.live="statusFilter" placeholder="All Status">
                            <flux:select.option value="approved">Approved</flux:select.option>
                            <flux:select.option value="rejected">Rejected</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>
            </div>

            <!-- History Table -->
            <div class="overflow-x-auto bg-white dark:bg-zinc-900 shadow rounded-xl border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-100 dark:bg-zinc-800">
                        <tr>
                            @foreach ([
                                'Reservation&nbsp;Code', 'Requester', 'Vehicle',
                                'Decision', 'Level', 'Decision Date', 'Actions'
                            ] as $heading)
                                <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                    {!! $heading !!}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700 text-sm text-zinc-800 dark:text-zinc-200">
                        @forelse ($approvalHistory as $approval)
                            <tr>
                                <!-- Code -->
                                <td class="px-4 py-3 font-medium">{{ $approval->reservation->reservation_code }}</td>

                                <!-- Requester -->
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $approval->reservation->requester->name }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $approval->reservation->requester->email }}
                                    </div>
                                </td>

                                <!-- Vehicle -->
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $approval->reservation->vehicle->name }}
                                        <div class="font-normal text-zinc-500 dark:text-zinc-400">
                                            ({{ $approval->reservation->vehicle->type }})
                                        </div>
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $approval->reservation->vehicle->license_plate }}
                                    </div>
                                </td>

                                <!-- Decision -->
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-1 rounded-full text-xs {{ $this->getStatusBadgeClass($approval->status) }}">
                                        {{ ucfirst($approval->status) }}
                                    </span>
                                </td>

                                <!-- Level -->
                                <td class="px-4 py-3">Level {{ $approval->level }}</td>

                                <!-- Date -->
                                <td class="px-4 py-3">
                                    {{ \Carbon\Carbon::parse($approval->approved_at)->format('M d, Y H:i') }}
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3">
                                    <flux:button
                                        wire:click="showDetail({{ $approval->id }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="eye" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                    No approval history found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-4 py-4 border-t border-zinc-200 dark:border-zinc-700">
                    {{ $approvalHistory->links() }}
                </div>
            </div>

            <!-- Detail Modal -->
            <flux:modal wire:model="showDetailModal" class="md:w-md">
                @if ($selectedApproval)
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">Approval Detail</flux:heading>
                            <flux:text class="mt-2">
                                Complete information for reservation
                                <strong>{{ $selectedApproval->reservation->reservation_code }}</strong>.
                            </flux:text>
                        </div>

                        <div class="space-y-3 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                            <div><strong>Requester:</strong> {{ $selectedApproval->reservation->requester->name }}</div>
                            <div><strong>Vehicle:</strong> {{ $selectedApproval->reservation->vehicle->name }} ({{ $selectedApproval->reservation->vehicle->license_plate }})</div>
                            <div><strong>Driver:</strong> {{ $selectedApproval->reservation->driver->name }}</div>
                            <div><strong>Decision:</strong> {{ ucfirst($selectedApproval->status) }}</div>
                            <div><strong>Level:</strong> {{ $selectedApproval->level }}</div>
                            <div><strong>Decision Date:</strong> {{ \Carbon\Carbon::parse($selectedApproval->approved_at)->format('M d, Y H:i') }}</div>
                            <div><strong>Comments:</strong> {{ $selectedApproval->comments ?: '—' }}</div>
                        </div>

                        <div class="flex">
                            <flux:spacer />
                            <flux:button wire:click="closeDetailModal" variant="primary">Close</flux:button>
                        </div>
                    </div>
                @endif
            </flux:modal>
        </div>
    </flux:main>
</section>

