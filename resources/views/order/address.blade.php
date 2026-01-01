@if(empty($name) && empty($address_line))
    <span class="text-muted">Not Available</span>
@else
    <div class="address-block">

        {{-- Name --}}
        <strong class="d-block mb-1">
            <span class="text-muted">Name:</span>
            {{ $name ?? '-' }}
        </strong>

        {{-- Contact (STRICTLY CONTACT) --}}
        @if(!empty($phone))
            <div class="text-sm">
                <i class="fas fa-phone-alt mr-1 text-muted"></i>
                {{ $phone }}
            </div>
        @endif

        @if(!empty($email))
            <div class="text-sm mb-2">
                <i class="fas fa-envelope mr-1 text-muted"></i>
                {{ $email }}
            </div>
        @endif

        {{-- Address --}}
        @if(!empty($address_line) || !empty($city))
            <hr class="my-1">
            <strong class="d-block mb-1 text-muted">Address:</strong>
            <div class="text-sm">
                {{ $address_line }}<br>
                {{ $city ?? '-' }},
                {{ $state ?? '-' }} - {{ $pincode ?? '-' }}
            </div>
        @endif

    </div>
@endif
