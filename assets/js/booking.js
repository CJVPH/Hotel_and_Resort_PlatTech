// @ts-nocheck
// ============================================
// BOOKING PAGE JAVASCRIPT — Room Wizard
// Steps: 1 Guest Info → 2 Room → 3 Dates → 4 Review
// ============================================

let roomPrices = {};
let rmCalInstance = null;
let rmSelCheckin  = null; // { dateStr, label }
let rmSelCheckout = null;
let currentBookedDates = [];
let rmCurrentStep = 1;

const roomInventory = {
    '2': [
        { id: 'regular-101', type: 'Regular', name: 'Regular Room 101',            number: '101' },
        { id: 'deluxe-201',  type: 'Deluxe',  name: 'Deluxe Room 201',             number: '201' },
        { id: 'vip-301',     type: 'VIP',     name: 'VIP Suite 301',               number: '301' },
        { id: 'regular-102', type: 'Regular', name: 'Regular Room 102',            number: '102' },
        { id: 'deluxe-202',  type: 'Deluxe',  name: 'Deluxe Room 202',             number: '202' },
        { id: 'vip-302',     type: 'VIP',     name: 'VIP Suite 302',               number: '302' }
    ],
    '8': [
        { id: 'regular-103', type: 'Regular', name: 'Regular Family Room 103',     number: '103' },
        { id: 'deluxe-203',  type: 'Deluxe',  name: 'Deluxe Family Suite 203',     number: '203' },
        { id: 'vip-303',     type: 'VIP',     name: 'VIP Family Suite 303',        number: '303' },
        { id: 'regular-104', type: 'Regular', name: 'Regular Family Room 104',     number: '104' },
        { id: 'deluxe-204',  type: 'Deluxe',  name: 'Deluxe Family Suite 204',     number: '204' },
        { id: 'vip-304',     type: 'VIP',     name: 'VIP Family Suite 304',        number: '304' }
    ],
    '20': [
        { id: 'regular-105', type: 'Regular', name: 'Regular Group Townhouse 105', number: '105' },
        { id: 'deluxe-205',  type: 'Deluxe',  name: 'Deluxe Group Townhouse 205',  number: '205' },
        { id: 'vip-305',     type: 'VIP',     name: 'VIP Group Townhouse 305',     number: '305' },
        { id: 'regular-106', type: 'Regular', name: 'Regular Group Townhouse 106', number: '106' },
        { id: 'deluxe-206',  type: 'Deluxe',  name: 'Deluxe Group Townhouse 206',  number: '206' },
        { id: 'vip-306',     type: 'VIP',     name: 'VIP Group Townhouse 306',     number: '306' }
    ]
};

// ============================================
// INIT
// ============================================
function restorePendingBooking() {
    const pendingData = sessionStorage.getItem('pendingBooking');
    const savedStep = sessionStorage.getItem('bookingStep');
    
    if (pendingData) {
        try {
            const data = JSON.parse(pendingData);
            // Restore form fields
            if (data.name) document.getElementById('name').value = data.name;
            if (data.email) document.getElementById('email').value = data.email;
            if (data.phone) document.getElementById('phone').value = data.phone;
            if (data.guests) document.getElementById('guests').value = data.guests;
            if (data.specialRequests) document.getElementById('specialRequests').value = data.specialRequests;
            
            // Restore hidden room data
            if (data.room) document.getElementById('room').value = data.room;
            if (data.roomData) document.getElementById('roomData').value = data.roomData;
            if (data.price) document.getElementById('price').value = data.price;
            if (data.checkin) document.getElementById('checkin').value = data.checkin;
            if (data.checkout) document.getElementById('checkout').value = data.checkout;
            if (data.nights) document.getElementById('nights').value = data.nights;
            
            // Show toast message
            rmToast('Welcome back! Your booking details have been restored.');
            
            // Go to the saved step (or step 4 if not specified)
            const step = parseInt(savedStep) || 4;
            rmGoStep(step);
            
            // Clear the saved data
            sessionStorage.removeItem('pendingBooking');
            sessionStorage.removeItem('bookingStep');
            
            // Refresh room preview if room was selected
            if (data.guests) {
                updateRoomPrices(data.guests);
                if (data.room) displayDetailedRoomPreview(document.querySelector('[data-room-id]')?.dataset.roomType, data.guests);
            }
        } catch (e) {
            console.log('Could not restore pending booking:', e);
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Clear room preview on fresh page load
    clearRoomPreview();
    
    const form = document.getElementById('bookingForm');
    if (form) form.addEventListener('submit', handleBookingSubmit);
    fetchRoomPrices();
    
    // Restore pending booking if user just logged in
    restorePendingBooking();

    // Resort carousel thumbnail clicks
    document.querySelectorAll('.resort-thumb').forEach(thumb => {
        thumb.addEventListener('click', function() {
            const mainImg = document.getElementById('resortMainImg');
            if (mainImg) mainImg.src = this.src;
            document.querySelectorAll('.resort-thumb').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

// ============================================
// WIZARD NAVIGATION
// ============================================
function rmGoStep(n) {
    for (let i = 1; i <= 4; i++) {
        const step = document.getElementById('rmStep' + i);
        const ws   = document.getElementById('rws' + i);
        if (step) step.style.display = i === n ? '' : 'none';
        if (ws) {
            ws.classList.toggle('active', i === n);
            ws.classList.toggle('done',   i < n);
        }
    }
    rmCurrentStep = n;
    if (n === 2) {
        const pax = document.getElementById('guests')?.value;
        if (pax) generateRoomCards(pax);
    }
    if (n === 3) initRmCalendar();
    if (n === 4) rmPopulateReview();
}

function rmNext(step) {
    if (step === 1) {
        const name  = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const pax   = document.getElementById('guests').value;
        rmClearErrs(['name','email','phone','guests']);
        if (!name)  { rmFieldErr('name',  'Please enter your full name.'); return; }
        if (!email) { rmFieldErr('email', 'Please enter your email address.'); return; }
        if (!phone) { rmFieldErr('phone', 'Please enter your phone number.'); return; }
        if (!pax)   { rmFieldErr('guests','Please select the number of guests.'); return; }
        generateRoomCards(pax);
        const sub = document.getElementById('rmStep2Sub');
        if (sub) sub.textContent = `Rooms available for ${document.getElementById('guests').options[document.getElementById('guests').selectedIndex].text}.`;
    }
    if (step === 2) {
        const room = document.getElementById('room').value;
        if (!room) { rmToast('Please select a room to continue.'); return; }
    }
    if (step === 3) {
        if (!rmSelCheckin || !rmSelCheckout) { rmToast('Please select both check-in and check-out dates.'); return; }
    }
    rmGoStep(step + 1);
}

// ============================================
// FIELD ERROR HELPERS
// ============================================
function rmFieldErr(id, msg) {
    const el = document.getElementById(id); if (!el) return;
    const wrap = el.closest('.form-group') || el.parentElement;
    wrap.classList.add('pv-input-err');
    let errEl = wrap.querySelector('.pv-field-error');
    if (!errEl) { errEl = document.createElement('div'); errEl.className = 'pv-field-error'; wrap.appendChild(errEl); }
    errEl.textContent = msg;
    el.focus();
    setTimeout(() => { wrap.classList.remove('pv-input-err'); errEl?.remove(); }, 4000);
}
function rmClearErrs(ids) {
    ids.forEach(id => {
        const el = document.getElementById(id); if (!el) return;
        const wrap = el.closest('.form-group') || el.parentElement;
        wrap.classList.remove('pv-input-err');
        wrap.querySelector('.pv-field-error')?.remove();
    });
}
function rmToast(msg) {
    // reuse pavilion toast if available, else alert
    if (typeof pvToast === 'function') { pvToast(false, msg); return; }
    alert(msg);
}

// ============================================
// STEP 2 — ROOM CARDS
// ============================================
function generateRoomCards(guests) {
    const roomGrid = document.getElementById('roomSelection');
    if (!roomGrid) return;
    const rooms = roomInventory[guests];
    if (!rooms || rooms.length === 0) {
        roomGrid.innerHTML = '<p style="color:#dc3545;text-align:center;padding:2rem;">No rooms available for this guest count.</p>';
        return;
    }

    const typeIcons  = { Regular: 'fa-bed', Deluxe: 'fa-star', VIP: 'fa-crown' };
    const typeColors = { Regular: '#28a745', Deluxe: '#fd7e14', VIP: '#6f42c1' };
    const typeDesc   = {
        Regular: 'Comfortable & affordable',
        Deluxe:  'Spacious with premium amenities',
        VIP:     'Luxury experience with full service'
    };

    // Get pre-selected type from URL param (passed via hidden input)
    const preType = (document.getElementById('preselectedRoomType')?.value || '').toLowerCase();

    // Build type filter tabs
    const types = [...new Set(rooms.map(r => r.type))];
    let filterHTML = '<div class="rm-type-filter">';
    filterHTML += `<button class="rm-type-btn ${!preType ? 'active' : ''}" data-type="all">All</button>`;
    types.forEach(t => {
        const isActive = preType === t.toLowerCase();
        filterHTML += `<button class="rm-type-btn ${isActive ? 'active' : ''}" data-type="${t}">${t}</button>`;
    });
    filterHTML += '</div>';

    let html = filterHTML + '<div class="rm-card-grid">';
    rooms.forEach(room => {
        const price = getRoomPrice(room.type, guests);
        const color = typeColors[room.type];
        const hidden = preType && preType !== room.type.toLowerCase() ? ' style="display:none;"' : '';
        html += `
        <div class="rm-room-card" data-room-id="${room.id}" data-room-type="${room.type}" data-room-number="${room.number}"
             onclick="selectRoom('${room.id}','${room.type}','${room.number}','${room.name}')"${hidden}>
            <div class="rm-card-number">${room.number}</div>
            <div class="rm-card-name">${room.type}</div>
            <div class="rm-card-desc">${room.name}</div>
            <div class="rm-card-price" id="price-${room.id}">₱${price.toLocaleString()}</div>
        </div>`;
    });
    html += '</div>';
    roomGrid.innerHTML = html;
    updateRoomPrices(guests);

    // Wire up filter buttons
    roomGrid.querySelectorAll('.rm-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            roomGrid.querySelectorAll('.rm-type-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.type;
            roomGrid.querySelectorAll('.rm-room-card').forEach(card => {
                card.style.display = (filter === 'all' || card.dataset.roomType === filter) ? '' : 'none';
            });
        });
    });
}

function selectRoom(roomId, roomType, roomNumber, roomName) {
    document.querySelectorAll('.rm-room-card').forEach(c => c.classList.remove('rm-selected'));
    const card = document.querySelector(`[data-room-id="${roomId}"]`);
    if (card) card.classList.add('rm-selected');

    document.getElementById('room').value     = `${roomType} - ${roomName}`;
    document.getElementById('roomData').value = JSON.stringify({
        individual_room: { room_id: roomId, room_type: roomType, room_number: roomNumber, room_name: roomName }
    });

    const guests = document.getElementById('guests')?.value || '0';
    if (guests) displayDetailedRoomPreview(roomType, guests);

    // Reset calendar when room changes
    if (rmCalInstance) { rmCalInstance.destroy(); rmCalInstance = null; }
    rmSelCheckin = null; rmSelCheckout = null;
    updateRmStaySummary();

    // Update date step label
    const lbl = document.getElementById('rmDateRoomLabel');
    if (lbl) lbl.textContent = `Selecting dates for: ${roomName}`;

    // Pre-fetch booked dates in background (no auto-advance)
    fetch(`api/get_room_availability.php?room_number=${roomNumber}&room_type=${roomType}&pax_group=${guests}`)
        .then(r => r.json())
        .then(data => { currentBookedDates = (data.success && data.booked_dates) ? data.booked_dates : []; })
        .catch(() => { currentBookedDates = []; });
}

// ============================================
// STEP 3 — INLINE RANGE CALENDAR
// ============================================
function initRmCalendar() {
    if (rmCalInstance) return;
    const today = new Date(); today.setHours(0,0,0,0);
    const disabledDates = currentBookedDates.map(r => ({ from: r.from, to: r.to }));

    rmCalInstance = flatpickr('#rmCalendar', {
        inline: true,
        mode: 'range',
        minDate: 'today',
        dateFormat: 'Y-m-d',
        disable: disabledDates,
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const d = dayElem.dateObj; if (!d) return;
            const ymd = d.toISOString().slice(0,10);
            if (d < today || dayElem.classList.contains('flatpickr-disabled')) {
                dayElem.classList.add(d >= today ? 'pv-booked' : 'pv-past');
            } else {
                dayElem.classList.add('pv-avail');
            }
        },
        onMonthChange: function(sel, str, fp) {
            fp.calendarContainer?.querySelectorAll('.flatpickr-day').forEach(dayElem => {
                if (!dayElem.dateObj) return;
                const d = dayElem.dateObj;
                dayElem.classList.remove('pv-past','pv-avail','pv-booked');
                if (d < today) dayElem.classList.add('pv-past');
                else if (dayElem.classList.contains('flatpickr-disabled')) dayElem.classList.add('pv-booked');
                else dayElem.classList.add('pv-avail');
            });
        },
        onChange: function(selectedDates) {
            if (selectedDates.length >= 1) {
                const d = selectedDates[0];
                rmSelCheckin = { dateStr: d.toISOString().slice(0,10), label: d.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) };
                rmSelCheckout = null;
            }
            if (selectedDates.length === 2) {
                const d = selectedDates[1];
                rmSelCheckout = { dateStr: d.toISOString().slice(0,10), label: d.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) };
            }
            updateRmStaySummary();
        }
    });
}

function updateRmStaySummary() {
    const ciEl  = document.getElementById('rmSelCheckin');
    const coEl  = document.getElementById('rmSelCheckout');
    const badge = document.getElementById('rmNightsBadge');
    const nEl   = document.getElementById('rmNightsCount');

    if (ciEl)  ciEl.textContent  = 'Check-in: '  + (rmSelCheckin?.label  || '—');
    if (coEl)  coEl.textContent  = 'Check-out: ' + (rmSelCheckout?.label || '—');

    if (rmSelCheckin && rmSelCheckout) {
        const nights = Math.round((new Date(rmSelCheckout.dateStr) - new Date(rmSelCheckin.dateStr)) / 86400000);
        if (nEl) nEl.textContent = nights;
        if (badge) { badge.style.display = 'inline-flex'; }

        // Update hidden fields
        document.getElementById('checkin').value  = rmSelCheckin.dateStr;
        document.getElementById('checkout').value = rmSelCheckout.dateStr;
        document.getElementById('nights').value   = nights + ' night' + (nights !== 1 ? 's' : '');

        // Price
        const selectedCard = document.querySelector('.rm-room-card.rm-selected');
        const guests = document.getElementById('guests')?.value;
        if (selectedCard && guests) {
            const rate  = getRoomPrice(selectedCard.dataset.roomType, guests);
            const total = rate * nights;
            document.getElementById('roomRate').textContent    = '₱' + rate.toLocaleString();
            document.getElementById('nightsCount').textContent = nights;
            document.getElementById('totalAmount').textContent = '₱' + total.toLocaleString();
            document.getElementById('price').value             = total;
        }
    } else {
        if (badge) badge.style.display = 'none';
    }
}

// ============================================
// STEP 4 — REVIEW
// ============================================
function rmPopulateReview() {
    const name    = document.getElementById('name').value.trim();
    const email   = document.getElementById('email').value.trim();
    const phone   = document.getElementById('phone').value.trim();
    const guests  = document.getElementById('guests');
    const gLabel  = guests?.options[guests.selectedIndex]?.text || '';
    const roomVal = document.getElementById('room').value;
    const nights  = document.getElementById('nights').value;
    const rate    = document.getElementById('roomRate').textContent;
    const total   = document.getElementById('totalAmount').textContent;
    const nCount  = document.getElementById('nightsCount').textContent;

    document.getElementById('rvRmName').textContent    = name;
    document.getElementById('rvRmContact').textContent = email + (phone ? ' · ' + phone : '');
    document.getElementById('rvRmRoom').textContent    = roomVal || '—';
    document.getElementById('rvRmGuests').textContent  = gLabel;
    document.getElementById('rvRmDates').textContent   = (rmSelCheckin?.label || '—') + ' → ' + (rmSelCheckout?.label || '—');
    document.getElementById('rvRmNights').textContent  = nights || '—';
    document.getElementById('rvRmRate').textContent    = rate;
    document.getElementById('rvRmNightCount').textContent = nCount;
    document.getElementById('rvRmTotal').textContent   = total;
}

// ============================================
// PRICES
// ============================================
function fetchRoomPrices() {
    fetch('api/get_room_prices.php')
        .then(r => r.json())
        .then(data => { if (data.success) roomPrices = data.prices; })
        .catch(() => {});
}

function getRoomPrice(roomType, guests) {
    if (roomPrices[roomType]?.[guests]) return roomPrices[roomType][guests];
    const defaults = {
        'Regular': { '2': 1500,  '8': 3000,  '20': 6000  },
        'Deluxe':  { '2': 2500,  '8': 4500,  '20': 8500  },
        'VIP':     { '2': 4000,  '8': 7000,  '20': 12000 }
    };
    return defaults[roomType]?.[guests] || 0;
}

function updateRoomPrices(guests) {
    document.querySelectorAll('.rm-room-card').forEach(card => {
        const el = document.getElementById(`price-${card.dataset.roomId}`);
        if (el) {
            const price = getRoomPrice(card.dataset.roomType, guests);
            el.innerHTML = `₱${price.toLocaleString()}`;
        }
    });
}

// ============================================
// ROOM PREVIEW (right panel)
// ============================================
function clearRoomPreview() {
    const carousel = document.getElementById('resortCarousel');
    const detail   = document.getElementById('roomDetail');
    if (carousel) carousel.style.display = '';
    if (detail)   { detail.style.display = 'none'; detail.innerHTML = ''; }
}

const roomDetails = {
    Regular: {
        2:  { title:"Regular Room - 2 Guests", description:"Clean and comfortable accommodation with essential amenities. Perfect for couples looking for good value.", amenities:[{icon:"fa-bed",text:"Queen Size Bed"},{icon:"fa-tv",text:"32-inch LED TV"},{icon:"fa-wifi",text:"Free WiFi"},{icon:"fa-snowflake",text:"Air Conditioning"},{icon:"fa-shower",text:"Private Bathroom with Hot Shower"},{icon:"fa-coffee",text:"Complimentary Coffee & Tea"}], inclusions:["Welcome drink","Daily housekeeping","Basic toiletries","Free parking","Pool access"] },
        8:  { title:"Regular Family Room - 4-8 Guests", description:"Budget-friendly family accommodation with double deck beds.", amenities:[{icon:"fa-bed",text:"1 Queen Bed + 3 Double Deck Beds"},{icon:"fa-tv",text:"32-inch LED TV"},{icon:"fa-wifi",text:"Free WiFi"},{icon:"fa-snowflake",text:"2 Air Conditioning Units"},{icon:"fa-shower",text:"Shared Bathroom with Hot Shower"},{icon:"fa-coffee",text:"Coffee & Tea Station"}], inclusions:["Welcome drinks","Daily housekeeping","Basic toiletries","Free parking","Pool access"] },
        20: { title:"Regular Group Townhouse - 10-20 Guests", description:"Two-story townhouse for large groups or family reunions.", amenities:[{icon:"fa-home",text:"2-Story Townhouse"},{icon:"fa-utensils",text:"Full Kitchen & Dining Area"},{icon:"fa-bed",text:"5 Bedrooms with Double Deck Beds"},{icon:"fa-wifi",text:"Free WiFi"},{icon:"fa-snowflake",text:"4 Air Conditioning Units"},{icon:"fa-shower",text:"3 Shared Bathrooms"}], inclusions:["Welcome snacks","Daily housekeeping","Basic toiletries","Group parking","Pool access","Kitchen utensils"] }
    },
    Deluxe: {
        2:  { title:"Deluxe Room - 2 Guests", description:"Comfortable room with better amenities and nicer furnishings.", amenities:[{icon:"fa-bed",text:"King Size Bed"},{icon:"fa-tv",text:"40-inch Smart TV"},{icon:"fa-wifi",text:"High-Speed WiFi"},{icon:"fa-snowflake",text:"Inverter Air Conditioning"},{icon:"fa-bath",text:"Private Bathroom with Bathtub"},{icon:"fa-mountain",text:"Balcony with Garden View"}], inclusions:["Welcome drink & snacks","Daily housekeeping","Quality toiletries","Free parking","Pool & gym access","Complimentary breakfast"] },
        8:  { title:"Deluxe Family Suite - 4-8 Guests", description:"Spacious family suite with separate sleeping areas.", amenities:[{icon:"fa-bed",text:"2 Queen Beds + 2 Single Beds"},{icon:"fa-tv",text:"2 Smart TVs"},{icon:"fa-wifi",text:"High-Speed WiFi"},{icon:"fa-snowflake",text:"3 Air Conditioning Units"},{icon:"fa-bath",text:"2 Private Bathrooms"},{icon:"fa-mountain",text:"Large Balcony"}], inclusions:["Family welcome package","Daily housekeeping","Quality toiletries","Free parking","Pool & gym access","Family breakfast"] },
        20: { title:"Deluxe Group Townhouse - 10-20 Guests", description:"Premium two-story townhouse with upgraded amenities.", amenities:[{icon:"fa-home",text:"2-Story Premium Townhouse"},{icon:"fa-utensils",text:"Full Kitchen with Premium Appliances"},{icon:"fa-bed",text:"5 Premium Bedrooms"},{icon:"fa-wifi",text:"High-Speed WiFi"},{icon:"fa-bath",text:"4 Private Bathrooms"},{icon:"fa-mountain",text:"Private Terrace"}], inclusions:["Group welcome reception","Daily housekeeping","Quality toiletries","Free parking","Resort facilities","Group breakfast"] }
    },
    VIP: {
        2:  { title:"VIP Suite - 2 Guests", description:"Premium suite with upgraded amenities and better service.", amenities:[{icon:"fa-bed",text:"King Size Premium Bed"},{icon:"fa-tv",text:"50-inch Smart TV"},{icon:"fa-wifi",text:"Premium High-Speed WiFi"},{icon:"fa-bath",text:"Luxury Bathroom with Jacuzzi"},{icon:"fa-mountain",text:"Private Balcony with Ocean View"},{icon:"fa-concierge-bell",text:"Priority Service"}], inclusions:["VIP welcome with wine","Priority check-in/out","Twice-daily housekeeping","Premium toiletries","Personal concierge","Spa discount","In-room breakfast"] },
        8:  { title:"VIP Family Suite - 4-8 Guests", description:"Premium family accommodation with personalized service.", amenities:[{icon:"fa-bed",text:"4 King Size Premium Beds"},{icon:"fa-tv",text:"Premium Entertainment Systems"},{icon:"fa-wifi",text:"High-Speed WiFi"},{icon:"fa-bath",text:"4 Premium Bathrooms"},{icon:"fa-mountain",text:"Private Terrace with Pool View"},{icon:"fa-concierge-bell",text:"Family Concierge"}], inclusions:["Family welcome package","Dedicated concierge","Enhanced housekeeping","Premium amenities","Transportation service","Spa discount"] },
        20: { title:"VIP Group Townhouse - 10-20 Guests", description:"Luxury two-story townhouse with premium amenities and dedicated service.", amenities:[{icon:"fa-home",text:"2-Story Luxury Townhouse"},{icon:"fa-utensils",text:"Gourmet Kitchen"},{icon:"fa-bed",text:"5 Premium Bedrooms"},{icon:"fa-wifi",text:"Premium WiFi Network"},{icon:"fa-bath",text:"5 Premium Bathrooms"},{icon:"fa-concierge-bell",text:"Group Concierge Team"}], inclusions:["Group welcome reception","Dedicated concierge team","Premium amenities","Private facilities","Spa & wellness","Complete event planning"] }
    }
};

function displayDetailedRoomPreview(roomType, paxCount) {
    const detail = document.getElementById('roomDetail');
    if (!roomType || !paxCount || !detail) return;
    const roomData = roomDetails[roomType]?.[paxCount];
    if (!roomData) {
        const carousel = document.getElementById('resortCarousel');
        if (carousel) carousel.style.display = 'none';
        detail.style.display = '';
        detail.innerHTML = '<div class="preview-placeholder"><i class="fas fa-exclamation-triangle"></i><p>Room details not available</p></div>';
        return;
    }
    const selectedRoom = document.querySelector('.rm-room-card.rm-selected');
    const roomNumber   = selectedRoom?.dataset.roomNumber || '';
    if (roomNumber) {
        fetch(`api/get_room_image.php?room_number=${roomNumber}&room_type=${roomType}&pax_group=${paxCount}`)
            .then(r => r.json())
            .then(data => renderPreview(roomData, data.success && data.images?.length ? data.images : null, roomNumber, roomType))
            .catch(() => renderPreview(roomData, null, roomNumber, roomType));
    } else {
        renderPreview(roomData, null, '', roomType);
    }
}

function renderPreview(roomData, images, roomNumber, roomType) {
    const carousel = document.getElementById('resortCarousel');
    const detail   = document.getElementById('roomDetail');
    if (!detail) return;
    if (carousel) carousel.style.display = 'none';
    detail.style.display = '';
    let imageHTML = '';
    if (images && images.length > 0) {
        imageHTML = `<div class="room-preview-gallery"><div class="gallery-main-image"><img id="main-room-image" src="${images[0].file_path}" alt="${roomData.title}" style="width:100%;height:250px;object-fit:cover;border-radius:15px;border:2px solid #C9A961;"></div><div class="gallery-thumbnails">${images.map((img,i)=>`<img class="gallery-thumbnail ${i===0?'active':''}" src="${img.file_path}" data-index="${i}" style="width:60px;height:60px;object-fit:cover;border-radius:8px;border:2px solid ${i===0?'#C9A961':'transparent'};cursor:pointer;flex-shrink:0;">`).join('')}</div></div>`;
    } else if (roomNumber) {
        imageHTML = `<div class="room-image-placeholder"><i class="fas fa-image"></i><p>Room ${roomNumber} - ${roomType}</p><p style="font-size:0.8rem;opacity:0.6;">No images uploaded yet</p></div>`;
    }
    let amenitiesHTML = '<div class="room-preview-amenities"><h4><i class="fas fa-star"></i> Amenities</h4><div class="amenities-preview-grid">';
    roomData.amenities.forEach(a => { amenitiesHTML += `<div class="amenity-preview-item"><i class="fas ${a.icon}"></i><span>${a.text}</span></div>`; });
    amenitiesHTML += '</div></div>';
    let inclusionsHTML = '<div class="room-preview-inclusions"><h4><i class="fas fa-gift"></i> Included Services</h4><ul class="inclusions-preview-list">';
    roomData.inclusions.forEach(inc => { inclusionsHTML += `<li><i class="fas fa-check-circle"></i> ${inc}</li>`; });
    inclusionsHTML += '</ul></div>';
    detail.innerHTML = `<div class="room-preview-card"><div class="room-preview-title"><h3>${roomData.title}</h3><p class="room-preview-desc">${roomData.description}</p></div>${imageHTML}${amenitiesHTML}${inclusionsHTML}</div>`;
    if (images && images.length > 1) {
        const thumbs  = detail.querySelectorAll('.gallery-thumbnail');
        const mainImg = detail.querySelector('#main-room-image');
        thumbs.forEach(t => { t.addEventListener('click', function() { const idx=parseInt(this.dataset.index); mainImg.src=images[idx].file_path; thumbs.forEach(th=>{th.style.border='2px solid transparent';th.classList.remove('active');}); this.style.border='2px solid #C9A961'; this.classList.add('active'); }); });
    }
}

// ============================================
// FORM SUBMISSION
// ============================================
function handleBookingSubmit(event) {
    const name  = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const room  = document.getElementById('room').value;
    const ci    = document.getElementById('checkin').value;
    const co    = document.getElementById('checkout').value;
    const price = document.getElementById('price').value;

    if (!name || !email || !room || !ci || !co || !price || parseFloat(price) <= 0) {
        event.preventDefault();
        rmToast('Please complete all steps before submitting.');
        return false;
    }

    // ── LOGIN CHECK ────────────────────────────────────────────────
    // Check if user is logged in. If not, save form data and redirect to login
    const isLoggedIn = document.getElementById('isUserLoggedIn')?.value === '1';
    if (!isLoggedIn) {
        event.preventDefault();
        
        // Save all booking form data to sessionStorage
        const bookingData = {
            name: name,
            email: email,
            phone: document.getElementById('phone').value,
            guests: document.getElementById('guests').value,
            room: room,
            roomData: document.getElementById('roomData').value,
            price: price,
            checkin: ci,
            checkout: co,
            specialRequests: document.getElementById('specialRequests')?.value || '',
            nights: document.getElementById('nights').value
        };
        sessionStorage.setItem('pendingBooking', JSON.stringify(bookingData));
        sessionStorage.setItem('bookingStep', '4'); // Resume from review step
        
        // Redirect to login with return URL
        window.location.href = 'login.php?return=booking';
        return false;
    }
    // ────────────────────────────────────────────────────────────────

    // Attach special requests to roomData
    const specialRequests = document.getElementById('specialRequests')?.value;
    const roomDataInput   = document.getElementById('roomData');
    if (roomDataInput?.value && specialRequests) {
        try { const rd = JSON.parse(roomDataInput.value); rd.special_requests = specialRequests; roomDataInput.value = JSON.stringify(rd); } catch(e) {}
    }

    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        setTimeout(() => { if (submitBtn.disabled) { submitBtn.disabled=false; submitBtn.innerHTML='<i class="fas fa-credit-card"></i> Confirm & Pay'; } }, 10000);
    }
    return true;
}

window.addEventListener('error', e => console.error('Booking error:', e.error));

// Reset room preview when user leaves the booking page
window.addEventListener('beforeunload', clearRoomPreview);
window.addEventListener('pagehide', clearRoomPreview);
