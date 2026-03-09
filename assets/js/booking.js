// ============================================
// BOOKING PAGE JAVASCRIPT
// ============================================

// Global variables
let roomPrices = {};
let checkinPicker = null;
let checkoutPicker = null;

// Room inventory - 18 total rooms with specific room numbers
const roomInventory = {
    '2': [
        // Top row
        { id: 'regular-101', type: 'Regular', name: 'Regular Room 101', number: '101', available: true },
        { id: 'deluxe-201', type: 'Deluxe', name: 'Deluxe Room 201', number: '201', available: true },
        { id: 'vip-301', type: 'VIP', name: 'VIP Suite 301', number: '301', available: true },
        // Bottom row
        { id: 'regular-102', type: 'Regular', name: 'Regular Room 102', number: '102', available: true },
        { id: 'deluxe-202', type: 'Deluxe', name: 'Deluxe Room 202', number: '202', available: true },
        { id: 'vip-302', type: 'VIP', name: 'VIP Suite 302', number: '302', available: true }
    ],
    '8': [
        // Top row
        { id: 'regular-103', type: 'Regular', name: 'Regular Family Room 103', number: '103', available: true },
        { id: 'deluxe-203', type: 'Deluxe', name: 'Deluxe Family Suite 203', number: '203', available: true },
        { id: 'vip-303', type: 'VIP', name: 'VIP Family Suite 303', number: '303', available: true },
        // Bottom row
        { id: 'regular-104', type: 'Regular', name: 'Regular Family Room 104', number: '104', available: true },
        { id: 'deluxe-204', type: 'Deluxe', name: 'Deluxe Family Suite 204', number: '204', available: true },
        { id: 'vip-304', type: 'VIP', name: 'VIP Family Suite 304', number: '304', available: true }
    ],
    '20': [
        // Top row
        { id: 'regular-105', type: 'Regular', name: 'Regular Group Townhouse 105', number: '105', available: true },
        { id: 'deluxe-205', type: 'Deluxe', name: 'Deluxe Group Townhouse 205', number: '205', available: true },
        { id: 'vip-305', type: 'VIP', name: 'VIP Group Townhouse 305', number: '305', available: true },
        // Bottom row
        { id: 'regular-106', type: 'Regular', name: 'Regular Group Townhouse 106', number: '106', available: true },
        { id: 'deluxe-206', type: 'Deluxe', name: 'Deluxe Group Townhouse 206', number: '206', available: true },
        { id: 'vip-306', type: 'VIP', name: 'VIP Group Townhouse 306', number: '306', available: true }
    ]
};

// Initialize booking page when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Booking page initialized');
    
    // Initialize Flatpickr date pickers first
    initializeDatePickers();
    
    // Initialize all booking functionality
    initializeBookingForm();
    initializeDateValidation();
    initializeRoomSelection();
    initializePriceCalculation();
    
    // Fetch room prices from admin system
    fetchRoomPrices();
    
    console.log('All booking systems initialized successfully');
});

// ============================================
// FLATPICKR DATE PICKER INITIALIZATION
// ============================================

function initializeDatePickers() {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Initialize check-in date picker
    checkinPicker = flatpickr("#checkin", {
        minDate: null, // Allow past dates
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        disableMobile: false,
        onChange: function(selectedDates, dateStr, instance) {
            // Update checkout minimum date
            if (checkoutPicker && selectedDates[0]) {
                const nextDay = new Date(selectedDates[0]);
                nextDay.setDate(nextDay.getDate() + 1);
                checkoutPicker.set('minDate', nextDay);
                
                // Add active class for styling
                instance.input.classList.add('active');
            }
            validateDates();
        },
        onOpen: function(selectedDates, dateStr, instance) {
            console.log('Check-in calendar opened');
        }
    });
    
    // Initialize check-out date picker
    checkoutPicker = flatpickr("#checkout", {
        minDate: null, // Allow past dates initially
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        disableMobile: false,
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates[0]) {
                instance.input.classList.add('active');
            }
            validateDates();
        },
        onOpen: function(selectedDates, dateStr, instance) {
            console.log('Check-out calendar opened');
        }
    });
    
    console.log('Flatpickr date pickers initialized');
}

// ============================================
// BOOKING FORM INITIALIZATION
// ============================================

function initializeBookingForm() {
    const form = document.getElementById('bookingForm');
    if (form) {
        form.addEventListener('submit', handleBookingSubmit);
    }
    
    // Initialize guest selection
    const guestsSelect = document.getElementById('guests');
    if (guestsSelect) {
        guestsSelect.addEventListener('change', handleGuestSelection);
    }
    
    // Set minimum date to today
    const checkinInput = document.getElementById('checkin');
    const checkoutInput = document.getElementById('checkout');
    const today = new Date().toISOString().split('T')[0];
    
    if (checkinInput) {
        checkinInput.min = today;
    }
    if (checkoutInput) {
        checkoutInput.min = today;
    }
}

// ============================================
// DATE VALIDATION
// ============================================

function initializeDateValidation() {
    const checkinInput = document.getElementById('checkin');
    const checkoutInput = document.getElementById('checkout');
    
    if (checkinInput) {
        checkinInput.addEventListener('change', validateDates);
    }
    
    if (checkoutInput) {
        checkoutInput.addEventListener('change', validateDates);
    }
}

function validateDates() {
    const checkinInput = document.getElementById('checkin');
    const checkoutInput = document.getElementById('checkout');
    const nightsInput = document.getElementById('nights');
    const nightsBadge = document.getElementById('nightsBadge');
    const nightsBadgeCount = document.getElementById('nightsBadgeCount');
    
    if (!checkinInput || !checkoutInput || !nightsInput) return false;
    
    const checkinDate = new Date(checkinInput.value);
    const checkoutDate = new Date(checkoutInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Validate check-in date is not in the past
    if (checkinDate < today) {
        checkinInput.setCustomValidity('Check-in date cannot be in the past');
        if (nightsBadge) nightsBadge.style.display = 'none';
        return false;
    }
    
    // Validate check-out date is after check-in date
    if (checkoutDate <= checkinDate) {
        checkoutInput.setCustomValidity('Check-out date must be after check-in date');
        nightsInput.value = '';
        if (nightsBadge) nightsBadge.style.display = 'none';
        return false;
    }
    
    // Calculate number of nights
    const timeDiff = checkoutDate.getTime() - checkinDate.getTime();
    const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
    
    // Clear custom validity if dates are valid
    checkinInput.setCustomValidity('');
    checkoutInput.setCustomValidity('');
    
    // Update nights display
    nightsInput.value = `${nights} night${nights > 1 ? 's' : ''}`;
    
    // Update nights badge
    if (nightsBadge && nightsBadgeCount) {
        nightsBadgeCount.textContent = nights;
        nightsBadge.style.display = 'flex';
    }
    
    // Update price calculation
    updatePriceCalculation();
    
    return true;
}

// ============================================
// ROOM SELECTION
// ============================================

function initializeRoomSelection() {
    // Room selection will be initialized when guests are selected
}

function handleGuestSelection() {
    const guestsSelect = document.getElementById('guests');
    const guests = guestsSelect.value;
    
    if (guests) {
        generateIndividualRooms(guests);
    } else {
        clearRoomSelection();
    }
}

function generateIndividualRooms(guests) {
    const roomGrid = document.getElementById('roomSelection');
    
    if (!roomGrid) {
        console.error('Room grid container not found');
        return;
    }
    
    const availableRooms = roomInventory[guests];
    if (!availableRooms || availableRooms.length === 0) {
        roomGrid.innerHTML = '<p style="color: #dc3545; text-align: center; padding: 2rem;">No rooms available for this guest count.</p>';
        return;
    }
    
    // Create room grid HTML
    let roomHTML = '<div class="room-grid">';
    
    availableRooms.forEach(room => {
        const roomClass = room.type.toLowerCase();
        roomHTML += `
            <div class="room-card ${roomClass}" data-room-id="${room.id}" data-room-type="${room.type}" data-room-number="${room.number}" onclick="selectRoom('${room.id}', '${room.type}', '${room.number}', '${room.name}')">
                <div class="room-number">${room.number}</div>
                <div class="room-type">${room.type}</div>
                <div class="room-name">${room.name}</div>
                <div class="room-price" id="price-${room.id}">₱0</div>
            </div>
        `;
    });
    
    roomHTML += '</div>';
    roomGrid.innerHTML = roomHTML;
    
    // Update room prices
    updateRoomPrices(guests);
}

function selectRoom(roomId, roomType, roomNumber, roomName) {
    console.log('selectRoom called with:', roomId, roomType, roomNumber, roomName);
    
    // Remove selection from all rooms
    document.querySelectorAll('.room-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked room
    const selectedCard = document.querySelector(`[data-room-id="${roomId}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
    }
    
    // Update hidden form fields
    const roomInput = document.getElementById('room');
    const roomDataInput = document.getElementById('roomData');
    
    if (roomInput) {
        roomInput.value = `${roomType} - ${roomName}`;
    }
    
    if (roomDataInput) {
        const roomData = {
            individual_room: {
                room_id: roomId,
                room_type: roomType,
                room_number: roomNumber,
                room_name: roomName
            }
        };
        roomDataInput.value = JSON.stringify(roomData);
    }
    
    // Get current pax count
    const guestsSelect = document.getElementById('guests');
    const paxCount = guestsSelect ? guestsSelect.value : null;
    
    // Display detailed room preview with amenities based on pax count
    console.log('About to call displayDetailedRoomPreview with:', roomType, 'and pax:', paxCount);
    if (paxCount) {
        displayDetailedRoomPreview(roomType, paxCount);
    } else {
        console.warn('No pax count selected, cannot show room preview');
    }
    
    // Update price calculation
    updatePriceCalculation();
}

function clearRoomSelection() {
    const roomGrid = document.getElementById('roomSelection');
    if (roomGrid) {
        roomGrid.innerHTML = '<p class="room-instruction">Please select number of guests first to see available rooms.</p>';
    }
    
    // Clear form fields
    const roomInput = document.getElementById('room');
    const roomDataInput = document.getElementById('roomData');
    
    if (roomInput) roomInput.value = '';
    if (roomDataInput) roomDataInput.value = '';
    
    // Clear price calculation
    updatePriceCalculation();
}

// ============================================
// PRICE CALCULATION
// ============================================

function initializePriceCalculation() {
    // Price calculation will be triggered by date and room selection changes
}

function fetchRoomPrices() {
    fetch('api/get_room_prices.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                roomPrices = data.prices;
                console.log('Room prices loaded:', roomPrices);
            } else {
                console.error('Failed to fetch room prices');
                // Use default prices
                roomPrices = {
                    'Regular': { 2: 1500, 8: 3000, 20: 6000 },
                    'Deluxe': { 2: 2500, 8: 4500, 20: 8500 },
                    'VIP': { 2: 4000, 8: 7000, 20: 12000 }
                };
            }
        })
        .catch(error => {
            console.error('Error fetching room prices:', error);
            // Use default prices
            roomPrices = {
                'Regular': { 2: 1500, 8: 3000, 20: 6000 },
                'Deluxe': { 2: 2500, 8: 4500, 20: 8500 },
                'VIP': { 2: 4000, 8: 7000, 20: 12000 }
            };
        });
}

function updateRoomPrices(guests) {
    const roomCards = document.querySelectorAll('.room-card');
    
    roomCards.forEach(card => {
        const roomType = card.dataset.roomType;
        const roomId = card.dataset.roomId;
        const priceElement = document.getElementById(`price-${roomId}`);
        
        if (roomPrices[roomType] && roomPrices[roomType][guests] && priceElement) {
            const price = roomPrices[roomType][guests];
            priceElement.textContent = `₱${price.toLocaleString()}`;
        }
    });
}

function updatePriceCalculation() {
    const selectedRoom = document.querySelector('.room-card.selected');
    const nightsInput = document.getElementById('nights');
    const guestsSelect = document.getElementById('guests');
    
    const roomRateElement = document.getElementById('roomRate');
    const nightsCountElement = document.getElementById('nightsCount');
    const totalAmountElement = document.getElementById('totalAmount');
    const priceInput = document.getElementById('price');
    
    if (!selectedRoom || !nightsInput.value || !guestsSelect.value) {
        // Reset price display
        if (roomRateElement) roomRateElement.textContent = '₱0';
        if (nightsCountElement) nightsCountElement.textContent = '0';
        if (totalAmountElement) totalAmountElement.textContent = '₱0';
        if (priceInput) priceInput.value = '0';
        return;
    }
    
    const roomType = selectedRoom.dataset.roomType;
    const guests = guestsSelect.value;
    const nightsText = nightsInput.value;
    const nights = parseInt(nightsText.match(/\d+/)[0]);
    
    if (roomPrices[roomType] && roomPrices[roomType][guests]) {
        const roomRate = roomPrices[roomType][guests];
        const totalAmount = roomRate * nights;
        
        // Update display
        if (roomRateElement) roomRateElement.textContent = `₱${roomRate.toLocaleString()}`;
        if (nightsCountElement) nightsCountElement.textContent = nights;
        if (totalAmountElement) totalAmountElement.textContent = `₱${totalAmount.toLocaleString()}`;
        if (priceInput) priceInput.value = totalAmount;
    }
}

// ============================================
// ROOM PREVIEW
// ============================================

// ============================================
// ROOM PREVIEW (Legacy - replaced by displayDetailedRoomPreview)
// ============================================

/*
function updateRoomPreview(roomType, roomNumber, roomName) {
    const previewContainer = document.getElementById('roomPreview');
    if (!previewContainer) return;
    
    const roomTypeClass = roomType.toLowerCase();
    const previewHTML = `
        <div class="room-preview-content">
            <div class="room-preview-info">
                <h4>${roomName}</h4>
                <p><strong>Room Number:</strong> ${roomNumber}</p>
                <p><strong>Room Type:</strong> ${roomType}</p>
                <p><strong>Features:</strong> ${getRoomFeatures(roomType)}</p>
            </div>
        </div>
    `;
    
    previewContainer.innerHTML = previewHTML;
}
*/

// ============================================
// DETAILED ROOM INFORMATION
// ============================================

// Room details data with amenities and inclusions - Realistic Philippine Hotel Setting
const roomDetails = {
    Regular: {
        2: {
            title: "Regular Room - 2 Guests",
            description: "Clean and comfortable accommodation with essential amenities. Perfect for couples looking for good value and basic comfort.",
            amenities: [
                { icon: "fa-bed", text: "Queen Size Bed with Clean Linens" },
                { icon: "fa-tv", text: "32-inch LED TV with Local Channels" },
                { icon: "fa-wifi", text: "Free WiFi Internet" },
                { icon: "fa-snowflake", text: "Air Conditioning" },
                { icon: "fa-shower", text: "Private Bathroom with Hot Shower" },
                { icon: "fa-coffee", text: "Complimentary Coffee & Tea" },
                { icon: "fa-phone", text: "Telephone" },
                { icon: "fa-towel", text: "Fresh Towels Daily" }
            ],
            inclusions: [
                "Welcome drink",
                "Daily housekeeping",
                "Basic toiletries (soap, shampoo)",
                "Free parking",
                "24/7 front desk",
                "Swimming pool access"
            ]
        },
        8: {
            title: "Regular Family Room - 4-8 Guests",
            description: "Budget-friendly family accommodation with double deck beds. Great for families or small groups who want to save on accommodation costs.",
            amenities: [
                { icon: "fa-bed", text: "1 Queen Bed + 3 Double Deck Beds" },
                { icon: "fa-tv", text: "32-inch LED TV with Local Channels" },
                { icon: "fa-wifi", text: "Free WiFi Internet" },
                { icon: "fa-snowflake", text: "2 Air Conditioning Units" },
                { icon: "fa-shower", text: "Shared Bathroom with Hot Shower" },
                { icon: "fa-coffee", text: "Coffee & Tea Station" },
                { icon: "fa-phone", text: "Telephone" },
                { icon: "fa-utensils", text: "Small Refrigerator" }
            ],
            inclusions: [
                "Welcome drinks for group",
                "Daily housekeeping",
                "Basic toiletries",
                "Free parking",
                "24/7 front desk",
                "Swimming pool access",
                "Extra towels and pillows"
            ]
        },
        20: {
            title: "Regular Group Townhouse - 10-20 Guests",
            description: "Two-story townhouse with spacious living areas. Perfect for large groups, family reunions, or budget-conscious organizations.",
            amenities: [
                { icon: "fa-home", text: "2-Story Townhouse Layout" },
                { icon: "fa-couch", text: "Spacious Living Room (Ground Floor)" },
                { icon: "fa-utensils", text: "Full Kitchen & Dining Area (Ground Floor)" },
                { icon: "fa-bed", text: "4 Bedrooms with Double Deck Beds (2nd Floor)" },
                { icon: "fa-bed", text: "1 Master Bedroom with Queen Bed (2nd Floor)" },
                { icon: "fa-tv", text: "2 LED TVs (Living Room & Master Bedroom)" },
                { icon: "fa-wifi", text: "Free WiFi Internet" },
                { icon: "fa-snowflake", text: "4 Air Conditioning Units" },
                { icon: "fa-shower", text: "3 Shared Bathrooms (1 Ground, 2 Upper)" },
                { icon: "fa-car", text: "Group Parking Area" }
            ],
            inclusions: [
                "Welcome group snacks",
                "Daily housekeeping",
                "Basic toiletries",
                "Free group parking",
                "24/7 front desk",
                "Swimming pool access",
                "Kitchen utensils & cookware",
                "Extra bedding available"
            ]
        }
    },
    Deluxe: {
        2: {
            title: "Deluxe Room - 2 Guests",
            description: "Comfortable room with better amenities and nicer furnishings. Good choice for couples who want more comfort and space.",
            amenities: [
                { icon: "fa-bed", text: "King Size Bed with Quality Linens" },
                { icon: "fa-tv", text: "40-inch Smart TV with Cable" },
                { icon: "fa-wifi", text: "High-Speed WiFi" },
                { icon: "fa-snowflake", text: "Inverter Air Conditioning" },
                { icon: "fa-bath", text: "Private Bathroom with Bathtub" },
                { icon: "fa-coffee", text: "Coffee & Tea Making Facilities" },
                { icon: "fa-couch", text: "Small Sitting Area" },
                { icon: "fa-utensils", text: "Mini Refrigerator" },
                { icon: "fa-mountain", text: "Balcony with Garden View" },
                { icon: "fa-phone", text: "Direct Dial Telephone" }
            ],
            inclusions: [
                "Welcome drink & snacks",
                "Daily housekeeping",
                "Quality toiletries",
                "Free parking",
                "24/7 front desk",
                "Pool & gym access",
                "Complimentary breakfast",
                "Room service available"
            ]
        },
        8: {
            title: "Deluxe Family Suite - 4-8 Guests",
            description: "Spacious family suite with separate sleeping areas. Comfortable accommodation for families who want more privacy and space.",
            amenities: [
                { icon: "fa-bed", text: "2 Queen Beds + 2 Single Beds" },
                { icon: "fa-tv", text: "2 Smart TVs with Cable" },
                { icon: "fa-wifi", text: "High-Speed WiFi" },
                { icon: "fa-snowflake", text: "3 Air Conditioning Units" },
                { icon: "fa-bath", text: "2 Private Bathrooms" },
                { icon: "fa-coffee", text: "Coffee & Tea Station" },
                { icon: "fa-couch", text: "Living Room Area" },
                { icon: "fa-utensils", text: "Large Refrigerator" },
                { icon: "fa-mountain", text: "Large Balcony" },
                { icon: "fa-gamepad", text: "Entertainment Area" }
            ],
            inclusions: [
                "Family welcome package",
                "Daily housekeeping",
                "Quality toiletries for all",
                "Free parking",
                "24/7 front desk",
                "Pool & gym access",
                "Family breakfast included",
                "Room service available",
                "Kids activities access"
            ]
        },
        20: {
            title: "Deluxe Group Townhouse - 10-20 Guests",
            description: "Premium two-story townhouse with upgraded amenities. Great for corporate groups, family reunions, or special events.",
            amenities: [
                { icon: "fa-home", text: "2-Story Premium Townhouse" },
                { icon: "fa-couch", text: "Large Living & Dining Areas (Ground Floor)" },
                { icon: "fa-utensils", text: "Full Kitchen with Premium Appliances" },
                { icon: "fa-bed", text: "4 Bedrooms with Quality Beds (2nd Floor)" },
                { icon: "fa-bed", text: "1 Master Suite with King Bed (2nd Floor)" },
                { icon: "fa-tv", text: "Multiple Smart TVs Throughout" },
                { icon: "fa-wifi", text: "High-Speed WiFi Network" },
                { icon: "fa-snowflake", text: "Central Air Conditioning" },
                { icon: "fa-bath", text: "4 Private Bathrooms (2 per floor)" },
                { icon: "fa-mountain", text: "Private Terrace" },
                { icon: "fa-users", text: "Function Room for Events" },
                { icon: "fa-car", text: "Private Parking Area" }
            ],
            inclusions: [
                "Group welcome reception",
                "Daily housekeeping team",
                "Quality toiletries & amenities",
                "Free group parking",
                "Front desk assistance",
                "Resort facilities access",
                "Group breakfast service",
                "Event planning assistance",
                "Sound system for events",
                "Kitchen utensils & cookware"
            ]
        }
    },
    VIP: {
        2: {
            title: "VIP Suite - 2 Guests",
            description: "Premium suite with upgraded amenities and better service. Perfect for special occasions or guests who want enhanced comfort.",
            amenities: [
                { icon: "fa-bed", text: "King Size Premium Bed with Quality Linens" },
                { icon: "fa-tv", text: "50-inch Smart TV with Premium Channels" },
                { icon: "fa-wifi", text: "Premium High-Speed WiFi" },
                { icon: "fa-snowflake", text: "Dual Air Conditioning" },
                { icon: "fa-bath", text: "Luxury Bathroom with Jacuzzi Tub" },
                { icon: "fa-coffee", text: "Premium Coffee Machine" },
                { icon: "fa-couch", text: "Separate Living Room" },
                { icon: "fa-utensils", text: "Mini Bar & Wine Fridge" },
                { icon: "fa-mountain", text: "Private Balcony with Ocean View" },
                { icon: "fa-spa", text: "In-Room Massage Chair" },
                { icon: "fa-headphones", text: "Sound System" },
                { icon: "fa-concierge-bell", text: "Priority Service" }
            ],
            inclusions: [
                "VIP welcome with wine",
                "Priority check-in/out",
                "Twice-daily housekeeping",
                "Premium toiletries & bathrobes",
                "Airport transfer service",
                "Personal concierge",
                "VIP lounge access",
                "Spa treatment discount",
                "In-room breakfast service",
                "Priority restaurant reservations",
                "Laundry service"
            ]
        },
        8: {
            title: "VIP Family Suite - 4-8 Guests",
            description: "Premium family accommodation with enhanced amenities and personalized service. Great for families who want upgraded comfort.",
            amenities: [
                { icon: "fa-bed", text: "4 King Size Premium Beds" },
                { icon: "fa-tv", text: "Premium Entertainment Systems" },
                { icon: "fa-wifi", text: "High-Speed WiFi Network" },
                { icon: "fa-snowflake", text: "Smart Climate Control" },
                { icon: "fa-bath", text: "4 Premium Bathrooms" },
                { icon: "fa-coffee", text: "Coffee Bar & Kitchen" },
                { icon: "fa-couch", text: "Multiple Living Areas" },
                { icon: "fa-utensils", text: "Premium Bar & Wine Selection" },
                { icon: "fa-mountain", text: "Private Terrace with Pool View" },
                { icon: "fa-spa", text: "Family Spa Area" },
                { icon: "fa-car", text: "Valet Parking" },
                { icon: "fa-gamepad", text: "Entertainment Room" },
                { icon: "fa-baby", text: "Kids Amenities" },
                { icon: "fa-concierge-bell", text: "Family Concierge" }
            ],
            inclusions: [
                "Family welcome package",
                "Dedicated concierge",
                "Enhanced housekeeping",
                "Premium amenities for all",
                "Transportation service",
                "Personal family assistant",
                "Priority facilities access",
                "Spa services discount",
                "Private dining available",
                "Family activities planning",
                "Childcare service available",
                "Shopping assistance"
            ]
        },
        20: {
            title: "VIP Group Townhouse - 10-20 Guests",
            description: "Luxury two-story townhouse with premium amenities and dedicated service. Perfect for executive groups or special celebrations.",
            amenities: [
                { icon: "fa-home", text: "2-Story Luxury Townhouse" },
                { icon: "fa-couch", text: "Premium Living & Entertainment Areas" },
                { icon: "fa-utensils", text: "Gourmet Kitchen with Premium Appliances" },
                { icon: "fa-bed", text: "4 Premium Bedrooms (2nd Floor)" },
                { icon: "fa-bed", text: "1 Master Suite with King Bed & Jacuzzi" },
                { icon: "fa-tv", text: "Advanced Entertainment Systems" },
                { icon: "fa-wifi", text: "Private WiFi Network" },
                { icon: "fa-snowflake", text: "Smart Climate Control System" },
                { icon: "fa-bath", text: "5 Premium Bathrooms" },
                { icon: "fa-mountain", text: "Private Rooftop Terrace" },
                { icon: "fa-spa", text: "Private Spa Room" },
                { icon: "fa-car", text: "Valet Parking Service" },
                { icon: "fa-users", text: "Executive Conference Room" },
                { icon: "fa-concierge-bell", text: "Dedicated Staff Team" },
                { icon: "fa-shield-alt", text: "Enhanced Security & Privacy" },
                { icon: "fa-microphone", text: "Professional Sound System" }
            ],
            inclusions: [
                "VIP group welcome",
                "Dedicated staff team",
                "24/7 premium service",
                "Enhanced amenities",
                "Transportation service",
                "Group concierge team",
                "Private facilities access",
                "Spa & wellness services",
                "Premium dining service",
                "Complete event planning",
                "Security & privacy service",
                "Business support services",
                "Professional kitchen service",
                "Customized experiences"
            ]
        }
    }
};

function getRoomFeatures(roomType) {
    const roomData = roomDetails[roomType];
    if (!roomData) return 'Standard amenities included';
    
    // Create a simple text summary for basic display
    const amenityTexts = roomData.amenities.map(amenity => amenity.text);
    return amenityTexts.slice(0, 4).join(', ') + (amenityTexts.length > 4 ? '...' : '');
}

// Enhanced room preview function with detailed amenities based on pax and room type
function displayDetailedRoomPreview(roomType, paxCount) {
    const previewContainer = document.getElementById('roomPreview');
    
    console.log('Displaying room preview for:', roomType, 'with pax:', paxCount);
    
    if (!roomType || !paxCount || !previewContainer) {
        console.error('Missing room type, pax count, or preview container');
        return;
    }
    
    const roomData = roomDetails[roomType] && roomDetails[roomType][paxCount];
    console.log('Room data:', roomData);

    if (!roomData) {
        console.error('No room data found for', roomType, 'with', paxCount, 'pax');
        previewContainer.innerHTML = `
            <div class="preview-placeholder">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Room details not available</p>
            </div>
        `;
        return;
    }

    // Get the selected room to show its specific image
    const selectedRoom = document.querySelector('.room-card.selected');
    let roomNumber = '';
    let roomImages = null;
    
    if (selectedRoom) {
        roomNumber = selectedRoom.dataset.roomNumber;
        
        // Load room images (multiple images)
        fetch(`api/get_room_image.php?room_number=${roomNumber}&room_type=${roomType}&pax_group=${paxCount}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.images && data.images.length > 0) {
                    roomImages = data.images;
                } else {
                    roomImages = null; // No images available
                }
                renderRoomPreview();
            })
            .catch(error => {
                console.error('Error loading room images:', error);
                roomImages = null; // No images available
                renderRoomPreview();
            });
    } else {
        renderRoomPreview();
    }
    
    function renderRoomPreview() {
        // Create room images gallery HTML
        let imageHTML = '';
        if (roomImages && roomImages.length > 0) {
            imageHTML = `
                <div class="room-preview-gallery">
                    <div class="gallery-main-image">
                        <img id="main-room-image" src="${roomImages[0].file_path}" alt="${roomData.title}" style="width: 100%; height: 250px; object-fit: cover; border-radius: 15px; border: 2px solid #C9A961;">
                    </div>
                    <div class="gallery-thumbnails" style="display: flex; gap: 0.5rem; margin-top: 1rem; overflow-x: auto; padding: 0.5rem 0;">
                        ${roomImages.map((img, index) => `
                            <img class="gallery-thumbnail ${index === 0 ? 'active' : ''}" 
                                 src="${img.file_path}" 
                                 alt="Room Image ${index + 1}"
                                 data-index="${index}"
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid ${index === 0 ? '#C9A961' : 'transparent'}; cursor: pointer; flex-shrink: 0;">
                        `).join('')}
                    </div>
                    <p style="text-align: center; color: #C9A961; font-size: 0.9rem; margin: 0.5rem 0 0 0;">
                        <i class="fas fa-camera"></i> Room ${roomNumber} - ${roomType} (${roomImages.length} photos)
                    </p>
                </div>
            `;
        } else if (roomNumber) {
            imageHTML = `
                <div class="room-image-placeholder">
                    <i class="fas fa-image"></i>
                    <p>Room ${roomNumber} - ${roomType}</p>
                    <p style="font-size: 0.8rem; opacity: 0.6;">No images uploaded yet</p>
                </div>
            `;
        }

        // Create all amenities HTML
        let amenitiesHTML = '';
        if (roomData.amenities && roomData.amenities.length > 0) {
            amenitiesHTML = '<div class="room-preview-amenities"><h4><i class="fas fa-star"></i> Amenities</h4><div class="amenities-preview-grid">';
            roomData.amenities.forEach(amenity => {
                amenitiesHTML += `
                    <div class="amenity-preview-item">
                        <i class="fas ${amenity.icon}"></i>
                        <span>${amenity.text}</span>
                    </div>
                `;
            });
            amenitiesHTML += '</div></div>';
        }

        // Create all inclusions HTML
        let inclusionsHTML = '';
        if (roomData.inclusions && roomData.inclusions.length > 0) {
            inclusionsHTML = '<div class="room-preview-inclusions"><h4><i class="fas fa-gift"></i> Included Services</h4><ul class="inclusions-preview-list">';
            roomData.inclusions.forEach(inclusion => {
                inclusionsHTML += `<li><i class="fas fa-check-circle"></i> ${inclusion}</li>`;
            });
            inclusionsHTML += '</ul></div>';
        }

        const finalHTML = `
            <div class="room-preview-card">
                <div class="room-preview-title">
                    <h3>${roomData.title}</h3>
                    <p class="room-preview-desc">${roomData.description}</p>
                </div>
                ${imageHTML}
                ${amenitiesHTML}
                ${inclusionsHTML}
            </div>
        `;

        console.log('Setting preview HTML for', roomType, paxCount, 'pax');
        previewContainer.innerHTML = finalHTML;
        
        // Add gallery functionality if images exist
        if (roomImages && roomImages.length > 1) {
            const thumbnails = previewContainer.querySelectorAll('.gallery-thumbnail');
            const mainImage = previewContainer.querySelector('#main-room-image');
            
            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', function() {
                    const index = parseInt(this.dataset.index);
                    
                    // Update main image
                    mainImage.src = roomImages[index].file_path;
                    
                    // Update thumbnail borders
                    thumbnails.forEach(thumb => {
                        thumb.style.border = '2px solid transparent';
                        thumb.classList.remove('active');
                    });
                    this.style.border = '2px solid #C9A961';
                    this.classList.add('active');
                });
            });
        }
    }
}

// ============================================
// FORM SUBMISSION
// ============================================

function handleBookingSubmit(event) {
    const form = event.target;
    const submitBtn = document.getElementById('submitBtn');
    
    // Validate form
    if (!validateBookingForm()) {
        event.preventDefault();
        return false;
    }
    
    // Add special requests to room data
    const specialRequests = document.getElementById('specialRequests').value;
    const roomDataInput = document.getElementById('roomData');
    
    if (roomDataInput.value && specialRequests) {
        try {
            const roomData = JSON.parse(roomDataInput.value);
            roomData.special_requests = specialRequests;
            roomDataInput.value = JSON.stringify(roomData);
        } catch (e) {
            console.error('Error updating room data:', e);
        }
    }
    
    // Disable submit button to prevent double submission
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Booking...';
        
        // Re-enable button after 10 seconds as fallback
        setTimeout(() => {
            if (submitBtn.disabled) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-credit-card"></i> Complete Booking';
            }
        }, 10000);
    }
    
    return true;
}

function validateBookingForm() {
    const requiredFields = ['name', 'email', 'phone', 'guests', 'checkin', 'checkout', 'room'];
    let isValid = true;
    let firstInvalidField = null;
    
    requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (field && !field.value.trim()) {
            field.style.borderColor = '#dc3545';
            field.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
            if (!firstInvalidField) {
                firstInvalidField = field;
            }
            isValid = false;
        } else if (field) {
            field.style.borderColor = '#e0e0e0';
            field.style.boxShadow = 'none';
        }
    });
    
    // Validate email format
    const emailField = document.getElementById('email');
    if (emailField && emailField.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailField.value)) {
            emailField.style.borderColor = '#dc3545';
            emailField.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
            if (!firstInvalidField) {
                firstInvalidField = emailField;
            }
            isValid = false;
        }
    }
    
    // Validate dates
    if (!validateDates()) {
        isValid = false;
        const checkinField = document.getElementById('checkin');
        if (checkinField && !firstInvalidField) {
            firstInvalidField = checkinField;
        }
    }
    
    // Validate price
    const priceInput = document.getElementById('price');
    if (priceInput && (!priceInput.value || parseFloat(priceInput.value) <= 0)) {
        showAlert('Please select a room and valid dates to calculate the price.', 'error');
        isValid = false;
    }
    
    if (!isValid) {
        if (firstInvalidField) {
            firstInvalidField.focus();
            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        showAlert('Please fill in all required fields correctly and select a room.', 'error');
    }
    
    return isValid;
}

// Show alert function
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlert = document.querySelector('.booking-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `booking-alert alert-${type}`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
        <button type="button" class="alert-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Insert at top of booking form
    const bookingCard = document.querySelector('.booking-card');
    if (bookingCard) {
        bookingCard.insertBefore(alert, bookingCard.firstChild);
        alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 5000);
}

// ============================================
// ERROR HANDLING
// ============================================

window.addEventListener('error', function(e) {
    console.error('Booking page error:', e.error);
});

// ============================================
// UTILITY FUNCTIONS
// ============================================

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}