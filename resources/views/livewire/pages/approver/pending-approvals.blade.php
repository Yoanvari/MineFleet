<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\ReservationApproval;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public $selectedApproval = null;
    public $showModal = false;
    public $comments = '';
    public $action = '';

    public function mount()
    {
        // Ensure user is approver
        if (Auth::user()->role !== 'approver') {
            abort(403, 'Unauthorized');
        }
    }

    public function with()
    {
        return [
            'pendingApprovals' => ReservationApproval::with([
                'reservation.vehicle',
                'reservation.driver',
                'reservation.requester',
                'reservation.location',
            ])
            ->where('approver_id', Auth::id())
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
        ];
    }

    public function openModal($approvalId, $action)
    {
        $this->selectedApproval = ReservationApproval::findOrFail($approvalId);
        $this->action = $action;
        $this->comments = '';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedApproval = null;
        $this->comments = '';
        $this->action = '';
    }

    public function processApproval()
    {
        $this->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        if (!$this->selectedApproval) return;

        $this->selectedApproval->update([
            'status' => $this->action,
            'comments' => $this->comments,
            'approved_at' => now(),
        ]);

        // Update reservation status if rejected
        if ($this->action === 'rejected') {
            $this->selectedApproval->reservation->update([
                'status' => 'rejected'
            ]);
        }

        // Check if all approvals are approved for this reservation
        if ($this->action === 'approved') {
            $reservation = $this->selectedApproval->reservation;
            $pendingApprovals = ReservationApproval::where('reservation_id', $reservation->id)
                ->where('status', 'pending')
                ->count();

            if ($pendingApprovals === 0) {
                $reservation->update(['status' => 'approved']);
            }
        }

        session()->flash('message', 'Approval processed successfully!');
        $this->closeModal();
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
}; ?>

<section class="w-full">
    <flux:main>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <flux:heading size="xl">Pending Approvals</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    Review and approve vehicle reservation requests
                </flux:text>
            </div>

            <!-- Flash Message -->
            @if (session('message'))
                <div class="p-4 mb-4 text-green-800 bg-green-100 dark:bg-green-900 dark:text-green-200 rounded">
                    {{ session('message') }}
                </div>
            @endif

            <!-- Approvals Table -->
            <div class="overflow-x-auto bg-white dark:bg-zinc-900 shadow rounded-xl border border-zinc-200 dark:border-zinc-700">
                @if ($pendingApprovals->count())
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-100 dark:bg-zinc-800">
                            <tr>
                                @foreach ([
                                    'Reservation Code', 'Vehicle',
                                    'Driver', 'Destination', 'Schedule',
                                    'Status', 'Level', 'Actions'
                                ] as $heading)
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ $heading }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700 text-sm text-zinc-800 dark:text-zinc-200">
                            @foreach ($pendingApprovals as $approval)
                                <tr>
                                    <!-- Reservation Code -->
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $approval->reservation->reservation_code }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $approval->reservation->purpose }}</div>
                                    </td>

                                    <!-- Requester -->
                                    {{-- <td class="px-4 py-3">
                                        <div class="font-medium">{{ $approval->reservation->requester->name }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $approval->reservation->requester->email }}</div>
                                    </td> --}}

                                    <!-- Vehicle -->
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $approval->reservation->vehicle->name }} 
                                            <div class="font-normal text-zinc-500 dark:text-zinc-400">
                                                ({{ $approval->reservation->vehicle->type }})
                                            </div>
                                        </div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $approval->reservation->vehicle->license_plate }}</div>
                                        
                                    </td>

                                    <!-- Driver -->
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $approval->reservation->driver->name }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $approval->reservation->driver->license_number }}</div>
                                    </td>

                                    <!-- Destination -->
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $approval->reservation->location->name }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $approval->reservation->location->region }}</div>
                                    </td>

                                    <!-- Schedule -->
                                    <td class="px-4 py-3">
                                        <div>{{ \Carbon\Carbon::parse($approval->reservation->start_datetime)->format('M d, Y H:i') }}</div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ \Carbon\Carbon::parse($approval->reservation->end_datetime)->format('M d, Y H:i') }}</div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-block text-xs px-2 py-1 rounded-full bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300">
                                            Pending
                                        </span>
                                    </td>

                                    <!-- Level -->
                                    <td class="px-4 py-3">
                                        <div class="font-medium">Level {{ $approval->level }}</div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <flux:button
                                                wire:click="openModal({{ $approval->id }}, 'approved')"
                                                variant="primary"
                                                color="green"
                                                size="sm"
                                                icon="check">
                                                Approve
                                            </flux:button>
                                            <flux:button
                                                wire:click="openModal({{ $approval->id }}, 'rejected')"
                                                variant="danger"
                                                size="sm"
                                                icon="x-circle">
                                                Reject
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="px-4 py-4 border-t border-zinc-200 dark:border-zinc-700">
                        {{ $pendingApprovals->links() }}
                    </div>
                @else
                    <div class="text-center px-4 py-12 text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="inbox" class="w-12 h-12 mx-auto mb-4" />
                        <p class="font-medium">No pending approvals</p>
                        <p class="text-sm">All reservation requests have been processed.</p>
                    </div>
                @endif
            </div>

            <!-- Approval Modal -->
            <flux:modal wire:model="showModal" class="md:w-md">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">
                            {{ ucfirst($action) }} Reservation
                        </flux:heading>
                        <flux:text class="mt-2">
                            {{ $action === 'approved'
                                ? 'Confirm you want to approve this reservation.'
                                : 'Please provide a reason for rejection.' }}
                        </flux:text>
                    </div>

                    @if ($selectedApproval)
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg space-y-2">
                            <div class="text-sm"><strong>Reservation Code:</strong> {{ $selectedApproval->reservation->reservation_code }}</div>
                            <div class="text-sm"><strong>Requester:</strong> {{ $selectedApproval->reservation->requester->name }}</div>
                            <div class="text-sm"><strong>Purpose:</strong> {{ $selectedApproval->reservation->purpose }}</div>
                            <div class="text-sm"><strong>Approval Level:</strong> {{ $selectedApproval->level }}</div>
                        </div>
                    @endif

                    <!-- Comments -->
                    <flux:field>
                        <flux:label>Comments {{ $action === 'rejected' ? '(Required)' : '(Optional)' }}</flux:label>
                        <flux:textarea
                            wire:model.defer="comments"
                            placeholder="Enter your comments..."
                            rows="4" />
                    </flux:field>

                    <div class="flex">
                        <flux:spacer />
                        <flux:button wire:click="closeModal" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button
                            wire:click="processApproval"
                            variant="primary"
                            color="{{ $action === 'approved' ? 'green' : 'red' }}">
                            {{ ucfirst($action) }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        </div>
    </flux:main>
</section>

