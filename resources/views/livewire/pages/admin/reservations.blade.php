<?php

use Livewire\Volt\Component;
use App\Models\{Vehicle, Driver, Location, Reservation};
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

new class extends Component {
    public $showForm = false;
    public $editingId = null;
    
    // Form properties
    public $vehicle_id = '';
    public $driver_id = '';
    public $destination_id = '';
    public $start_datetime = '';
    public $end_datetime = '';
    public $purpose = '';
    public $status = 'pending';
    
    // Data collections
    public $vehicles = [];
    public $drivers = [];
    public $locations = [];
    public $reservations = [];
    
    // Search and filters
    public $search = '';
    public $statusFilter = '';
    public $vehicleFilter = '';
    
    public function mount()
    {
        $this->loadData();
        $this->loadReservations();
    }
    
    public function loadData()
    {
        $this->vehicles = Vehicle::where('status', 'available')->get();
        $this->drivers = Driver::where('is_available', true)->get();
        $this->locations = Location::where('type', 'mine_site')->get();
    }
    
    public function loadReservations()
    {
        $query = Reservation::with(['vehicle', 'driver', 'location', 'requester'])
            ->where('requester_id', auth()->id());
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('reservation_code', 'like', '%' . $this->search . '%')
                  ->orWhere('purpose', 'like', '%' . $this->search . '%')
                  ->orWhereHas('driver', function($dq) {
                        $dq->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('location', function($lq) {
                        $lq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }
        
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        if ($this->vehicleFilter) {
            $query->where('vehicle_id', $this->vehicleFilter);
        }
        
        $this->reservations = $query->orderBy('start_datetime', 'desc')->get();
    }
    
    public function openForm()
    {
        $this->showForm = true;
        $this->resetForm();
    }
    
    public function closeForm()
    {
        $this->showForm = false;
        $this->resetForm();
    }
    
    public function resetForm()
    {
        $this->editingId = null;
        $this->vehicle_id = '';
        $this->driver_id = '';
        $this->destination_id = '';
        $this->start_datetime = '';
        $this->end_datetime = '';
        $this->purpose = '';
        $this->status = 'pending';
    }
    
    public function save()
    {
        $this->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:drivers,id',
            'destination_id' => 'required|exists:locations,id',
            'start_datetime' => 'required|date|after:now',
            'end_datetime' => 'required|date|after:start_datetime',
            'purpose' => 'required|string|max:255',
        ]);
        
        // Check for vehicle availability
        $conflictingReservation = Reservation::where('vehicle_id', $this->vehicle_id)
            ->where('status', '!=', 'cancelled')
            ->where(function($q) {
                $q->whereBetween('start_datetime', [$this->start_datetime, $this->end_datetime])
                  ->orWhereBetween('end_datetime', [$this->start_datetime, $this->end_datetime])
                  ->orWhere(function($q2) {
                      $q2->where('start_datetime', '<=', $this->start_datetime)
                         ->where('end_datetime', '>=', $this->end_datetime);
                  });
            });
            
        if ($this->editingId) {
            $conflictingReservation->where('id', '!=', $this->editingId);
        }
        
        if ($conflictingReservation->exists()) {
            $this->addError('vehicle_id', 'Vehicle is not available for the selected time period.');
            return;
        }
        
        $data = [
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'destination_id' => $this->destination_id,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'requester_id' => auth()->id(),
        ];
        
        if ($this->editingId) {
            $reservation = Reservation::findOrFail($this->editingId);
            $reservation->update($data);
            session()->flash('message', 'Reservation updated successfully!');
        } else {
            $data['reservation_code'] = 'RES-' . strtoupper(Str::random(8));
            Reservation::create($data);
            session()->flash('message', 'Reservation created successfully!');
        }
        
        $this->closeForm();
        $this->loadReservations();
    }
    
    public function edit($id)
    {
        $reservation = Reservation::findOrFail($id);
        
        if ($reservation->requester_id !== auth()->id()) {
            session()->flash('error', 'You can only edit your own reservations.');
            return;
        }
        
        if ($reservation->status === 'approved') {
            session()->flash('error', 'Cannot edit approved reservations.');
            return;
        }
        
        $this->editingId = $id;
        $this->vehicle_id = $reservation->vehicle_id;
        $this->driver_id = $reservation->driver_id;
        $this->destination_id = $reservation->destination_id;
        $this->start_datetime = $reservation->start_datetime;
        $this->end_datetime = $reservation->end_datetime;
        $this->purpose = $reservation->purpose;
        $this->status = $reservation->status;
        
        $this->showForm = true;
    }
    
    public function delete($id)
    {
        $reservation = Reservation::findOrFail($id);
        
        if ($reservation->requester_id !== auth()->id()) {
            session()->flash('error', 'You can only delete your own reservations.');
            return;
        }
        
        if ($reservation->status === 'approved') {
            session()->flash('error', 'Cannot delete approved reservations.');
            return;
        }
        
        $reservation->delete();
        session()->flash('message', 'Reservation deleted successfully!');
        $this->loadReservations();
    }
    
    public function updatedSearch()
    {
        $this->loadReservations();
    }
    
    public function updatedStatusFilter()
    {
        $this->loadReservations();
    }
    
    public function updatedVehicleFilter()
    {
        $this->loadReservations();
    }
    
    public function getStatusVariant($status)
    {
        return match($status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'neutral',
            default => 'neutral',
        };
    }
}; ?>

<section class="w-full">
    <flux:main>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex justify-between items-center">
                <flux:heading size="xl">Vehicle Reservations</flux:heading>
                <flux:button wire:click="openForm" variant="primary" icon="plus">
                    New Reservation
                </flux:button>
            </div>

            <!-- Flash Messages -->
            @if (session()->has('message'))
                <div class="p-4 mb-4 text-green-800 bg-green-100 rounded">
                    {{ session('message') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="p-4 mb-4 text-red-800 bg-red-100 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Form Modal -->
            <flux:modal wire:model="showForm" class="md:w-2xl">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">
                            {{ $editingId ? 'Edit Reservation' : 'New Reservation' }}
                        </flux:heading>
                        <flux:text class="mt-2">
                            {{ $editingId ? 'Update reservation details below.' : 'Create a new vehicle reservation.' }}
                        </flux:text>
                    </div>
                    
                    <form wire:submit="save" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Vehicle -->
                            <flux:field>
                                <flux:label>Vehicle</flux:label>
                                <flux:select wire:model="vehicle_id" placeholder="Select Vehicle">
                                    @foreach ($vehicles as $vehicle)
                                        <flux:select.option value="{{ $vehicle->id }}">
                                            {{ $vehicle->name }} ({{ $vehicle->license_plate }})
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="vehicle_id" />
                            </flux:field>
                            
                            <!-- Driver -->
                            <flux:field>
                                <flux:label>Driver</flux:label>
                                <flux:select wire:model="driver_id" placeholder="Select Driver">
                                    @foreach ($drivers as $driver)
                                        <flux:select.option value="{{ $driver->id }}">
                                            {{ $driver->name }} ({{ $driver->license_number }})
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="driver_id" />
                            </flux:field>
                            
                            <!-- Destination -->
                            <flux:field>
                                <flux:label>Destination</flux:label>
                                <flux:select wire:model="destination_id" placeholder="Select Destination">
                                    @foreach ($locations as $location)
                                        <flux:select.option value="{{ $location->id }}">
                                            {{ $location->name }} ({{ $location->region }})
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="destination_id" />
                            </flux:field>
                            
                            <!-- Status (for edit) -->
                            @if ($editingId)
                                <flux:field>
                                    <flux:label>Status</flux:label>
                                    <flux:select wire:model="status">
                                        <flux:select.option value="pending">Pending</flux:select.option>
                                        <flux:select.option value="cancelled">Cancelled</flux:select.option>
                                    </flux:select>
                                </flux:field>
                            @endif
                            
                            <!-- Start Date -->
                            <flux:field>
                                <flux:label>Start Date & Time</flux:label>
                                <flux:input type="datetime-local" wire:model="start_datetime" />
                                <flux:error name="start_datetime" />
                            </flux:field>
                            
                            <!-- End Date -->
                            <flux:field>
                                <flux:label>End Date & Time</flux:label>
                                <flux:input type="datetime-local" wire:model="end_datetime" />
                                <flux:error name="end_datetime" />
                            </flux:field>
                        </div>
                        
                        <!-- Purpose -->
                        <flux:field>
                            <flux:label>Purpose</flux:label>
                            <flux:textarea wire:model="purpose" placeholder="Describe the purpose of this reservation..." rows="3" />
                            <flux:error name="purpose" />
                        </flux:field>
                        
                        <div class="flex">
                            <flux:spacer />
                            <flux:button wire:click="closeForm" variant="ghost">
                                Cancel
                            </flux:button>
                            <flux:button wire:click="save" variant="primary">
                                {{ $editingId ? 'Update' : 'Create' }} Reservation
                            </flux:button>
                        </div>
                    </form>
                </div>
            </flux:modal>

            <!-- Filters -->
            <div class="p-4 bg-white dark:bg-zinc-900 shadow rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-7 gap-4">
                    <!-- Search -->
                    <flux:field class="md:col-span-3">
                        <flux:label>Search</flux:label>
                        <flux:input 
                            wire:model.live="search" 
                            placeholder="Search by code, purpose, driver, or destination..."
                            icon="magnifying-glass"
                        />
                    </flux:field>
                    
                    <!-- Status Filter -->
                    <flux:field class="md:col-span-2">
                        <flux:label>Status</flux:label>
                        <flux:select wire:model.live="statusFilter" placeholder="All Status">
                            <flux:select.option value="pending">Pending</flux:select.option>
                            <flux:select.option value="approved">Approved</flux:select.option>
                            <flux:select.option value="rejected">Rejected</flux:select.option>
                            <flux:select.option value="cancelled">Cancelled</flux:select.option>
                        </flux:select>
                    </flux:field>
                    
                    <!-- Vehicle Filter -->
                    <flux:field class="md:col-span-2">
                        <flux:label>Vehicle</flux:label>
                        <flux:select wire:model.live="vehicleFilter" placeholder="All Vehicles">
                            @foreach ($vehicles as $vehicle)
                                <flux:select.option value="{{ $vehicle->id }}">{{ $vehicle->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="overflow-x-auto bg-white dark:bg-zinc-900 shadow rounded-xl border border-zinc-200 dark:border-zinc-700 mt-6">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-100 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">Code</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">Vehicle</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">Driver</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">Destination</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">Period</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">Status</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700 text-sm text-zinc-800 dark:text-zinc-200">
                        @forelse ($reservations as $reservation)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $reservation->reservation_code }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $reservation->purpose }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $reservation->vehicle->name }}
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $reservation->vehicle->type }} ({{ $reservation->vehicle->license_plate }})</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $reservation->driver->name }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $reservation->driver->phone }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $reservation->location->name }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $reservation->location->region }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ \Carbon\Carbon::parse($reservation->start_datetime)->format('M d, Y H:i') }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ \Carbon\Carbon::parse($reservation->end_datetime)->format('M d, Y H:i') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block text-xs px-2 py-1 rounded-full
                                        @switch($reservation->status)
                                            @case('approved') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 @break
                                            @case('rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 @break
                                            @case('cancelled') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 @break
                                            @default bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300
                                        @endswitch">
                                        {{ ucfirst($reservation->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        @if ($reservation->status === 'pending')
                                        <flux:button 
                                            wire:click="edit({{ $reservation->id }})" 
                                            variant="ghost" 
                                            size="sm"
                                            icon="pencil"
                                        >
                                        </flux:button>
                                        <flux:button 
                                            wire:click="delete({{ $reservation->id }})" 
                                            wire:confirm="Are you sure you want to delete this reservation?"
                                            variant="ghost" 
                                            size="sm"
                                            icon="trash"
                                        >
                                        </flux:button>
                                        @else
                                            <span class="text-zinc-400 dark:text-zinc-500">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center px-4 py-8 text-zinc-500 dark:text-zinc-400">
                                    No reservations found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </flux:main>
</section>