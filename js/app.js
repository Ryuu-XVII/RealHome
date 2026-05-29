/**
 * RealHome Core Script
 * Handles preloaders, scroll-reveal, and wishlist syncing.
 */

document.addEventListener('DOMContentLoaded', () => {
    // -----------------------------------------------------------------
    // 1. Site Preloader & Header Scroller
    // -----------------------------------------------------------------
    const preloader = document.getElementById('preloader');
    if (preloader) {
        window.addEventListener('load', () => {
            preloader.classList.add('loaded');
        });
        // Fallback in case loading takes too long
        setTimeout(() => {
            preloader.classList.add('loaded');
        }, 3000);
    }

    const header = document.querySelector('header.site-header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 30) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // -----------------------------------------------------------------
    // 2. Scroll Reveal Animations (IntersectionObserver)
    // -----------------------------------------------------------------
    const revealElements = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && revealElements.length > 0) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -50px 0px'
        });

        revealElements.forEach(el => revealObserver.observe(el));
    } else {
        revealElements.forEach(el => el.classList.add('active'));
    }

    // -----------------------------------------------------------------
    // 3. Saved Properties Wishlist Manager (LocalStorage & DB Sync)
    // -----------------------------------------------------------------
    const WISHLIST_KEY = 'realhome_wishlist';
    let isUserLoggedInClient = false;

    // Helper: Retrieve favorites from localStorage
    window.getWishlist = function() {
        try {
            return JSON.parse(localStorage.getItem(WISHLIST_KEY)) || [];
        } catch (e) {
            return [];
        }
    };

    // Helper: Save favorites to localStorage
    window.saveWishlist = function(list) {
        localStorage.setItem(WISHLIST_KEY, JSON.stringify(list));
        updateWishlistBadge();
        syncWishlistButtons();
        renderWishlistDrawer();
    };

    // Toggle a listing saved status (Supports both Guest LocalStorage & Client Database AJAX!)
    window.toggleWishlist = function(propertyId, title, price, imgUrl, listingType, address) {
        if (isUserLoggedInClient) {
            // User is a logged-in Client: perform AJAX sync and toggle
            const formData = new FormData();
            formData.append('property_id', propertyId);

            fetch('wishlist_ajax.php?action=toggle', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let list = getWishlist();
                    if (data.status === 'added') {
                        list.push({
                            id: propertyId,
                            title: title,
                            price: price,
                            img: imgUrl,
                            type: listingType,
                            address: address
                        });
                        showNotification('Saved to your Account Wishlist!', 'success');
                    } else {
                        list = list.filter(item => item.id != propertyId);
                        showNotification('Removed from your Account Wishlist', 'info');
                    }
                    saveWishlist(list);
                } else {
                    showNotification('Database error updating favorites.', 'info');
                }
            })
            .catch(() => {
                showNotification('Connection error updating wishlist.', 'info');
            });
        } else {
            // Guest User: normal LocalStorage flow
            let list = getWishlist();
            const existingIdx = list.findIndex(item => item.id == propertyId);

            if (existingIdx !== -1) {
                list.splice(existingIdx, 1);
                showNotification('Removed from Saved Properties', 'info');
            } else {
                list.push({
                    id: propertyId,
                    title: title,
                    price: price,
                    img: imgUrl,
                    type: listingType,
                    address: address
                });
                showNotification('Added to Saved Properties!', 'success');
            }
            saveWishlist(list);
        }
    };

    // Remove item specifically from inside wishlist drawer
    window.removeWishlistItem = function(propertyId) {
        if (isUserLoggedInClient) {
            const formData = new FormData();
            formData.append('property_id', propertyId);

            fetch('wishlist_ajax.php?action=toggle', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.status === 'removed') {
                    let list = getWishlist();
                    list = list.filter(item => item.id != propertyId);
                    saveWishlist(list);
                    showNotification('Removed from Saved Properties', 'info');
                }
            });
        } else {
            let list = getWishlist();
            list = list.filter(item => item.id != propertyId);
            saveWishlist(list);
            showNotification('Removed from Saved Properties', 'info');
        }
    };

    // Sync wishlist count badge
    function updateWishlistBadge() {
        const badge = document.querySelector('.wishlist-badge');
        if (badge) {
            const count = getWishlist().length;
            badge.innerText = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    // Update heart buttons active state across the DOM
    function syncWishlistButtons() {
        const list = getWishlist();
        const activeIds = list.map(item => String(item.id));

        document.querySelectorAll('.card-wishlist-btn').forEach(btn => {
            const id = btn.getAttribute('data-id');
            if (activeIds.includes(String(id))) {
                btn.classList.add('active');
                btn.innerHTML = '❤️';
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '🤍';
            }
        });

        const detailBtn = document.querySelector('.detail-wishlist-btn');
        if (detailBtn) {
            const id = detailBtn.getAttribute('data-id');
            if (activeIds.includes(String(id))) {
                detailBtn.classList.add('active');
                detailBtn.innerHTML = '❤️ Saved in Favorites';
            } else {
                detailBtn.classList.remove('active');
                detailBtn.innerHTML = '🤍 Save to Favorites';
            }
        }
    }

    // Generate HTML elements inside the sliding drawer
    function renderWishlistDrawer() {
        const container = document.querySelector('.wishlist-items-container');
        if (!container) return;

        const list = getWishlist();
        if (list.length === 0) {
            container.innerHTML = `
                <div class="wishlist-empty-state">
                    <p style="font-size: 40px; margin-bottom: 10px;">⭐</p>
                    <p>You have no saved properties yet.</p>
                    <p style="font-size: 13px; margin-top: 5px;">Browse our listings and click the heart icon to save favorites!</p>
                </div>
            `;
            return;
        }

        let html = '';
        list.forEach(item => {
            html += `
                <div class="wishlist-item">
                    <img src="${item.img}" alt="Property Image" class="wishlist-item-img">
                    <div class="wishlist-item-details">
                        <div class="wishlist-item-price">${item.price}</div>
                        <a href="property_details.php?id=${item.id}" class="wishlist-item-title" style="text-decoration:none; font-weight:600; color:white; font-size:13px;">${item.title}</a>
                        <div style="font-size: 11px; color:#64748b;">${item.address}</div>
                    </div>
                    <button class="wishlist-item-remove" onclick="removeWishlistItem('${item.id}')" title="Remove">&times;</button>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    // Interactive sliding wishlist drawer listeners
    const openBtn = document.querySelector('.wishlist-toggle-btn');
    const drawer = document.querySelector('.wishlist-drawer');
    const overlay = document.querySelector('.wishlist-overlay');
    const closeBtn = document.querySelector('.wishlist-close-btn');

    if (openBtn && drawer && overlay) {
        const toggleDrawer = () => {
            drawer.classList.toggle('active');
            overlay.classList.toggle('active');
            if (drawer.classList.contains('active')) {
                renderWishlistDrawer();
            }
        };

        openBtn.addEventListener('click', toggleDrawer);
        if (closeBtn) closeBtn.addEventListener('click', toggleDrawer);
        overlay.addEventListener('click', toggleDrawer);
    }

    // -----------------------------------------------------------------
    // 4. Client Database Wishlist Synchronization Engine
    // -----------------------------------------------------------------
    function fetchAndSyncDatabaseWishlist() {
        fetch('wishlist_ajax.php?action=get')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                isUserLoggedInClient = true;
                const dbFavoriteIds = data.favorites.map(id => String(id));
                const localList = getWishlist();
                
                // Identify any local wishlist items NOT yet saved in the DB
                const unsyncedIds = localList
                    .map(item => String(item.id))
                    .filter(id => !dbFavoriteIds.includes(id));
                
                if (unsyncedIds.length > 0) {
                    // Sync outstanding guest properties up to DB
                    const formData = new FormData();
                    formData.append('property_ids', JSON.stringify(unsyncedIds));

                    fetch('wishlist_ajax.php?action=sync', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => r.json())
                    .then(syncRes => {
                        if (syncRes.success) {
                            showNotification('Synced guest favorites to account!', 'success');
                            // Re-fetch all favorites now that they are synchronized
                            fetchAndSyncDatabaseWishlist();
                        }
                    });
                } else {
                    // All synced! Build local structures based on authoritative DB listings
                    // Rebuild localStorage list with complete matching details
                    // We will query listing page details or fallbacks
                    const syncedList = [];
                    // Keep detailed objects for matching DB ids, otherwise use placeholders
                    dbFavoriteIds.forEach(dbId => {
                        const existingMatch = localList.find(item => String(item.id) === dbId);
                        if (existingMatch) {
                            syncedList.push(existingMatch);
                        } else {
                            // Fetch mock placeholder details for synchronization drawer (details updated on click)
                            syncedList.push({
                                id: dbId,
                                title: 'Premium Property',
                                price: 'Explore Details',
                                img: 'images/hero_house_bg.png',
                                type: 'buy',
                                address: 'Saved in Account'
                            });
                        }
                    });
                    
                    saveWishlist(syncedList);
                }
            } else {
                isUserLoggedInClient = false;
                // Keep standard guest UI
                updateWishlistBadge();
                syncWishlistButtons();
                renderWishlistDrawer();
            }
        })
        .catch(() => {
            isUserLoggedInClient = false;
            updateWishlistBadge();
            syncWishlistButtons();
            renderWishlistDrawer();
        });
    }

    // Trigger dynamic fetch on startup
    fetchAndSyncDatabaseWishlist();

    // -----------------------------------------------------------------
    // 5. Toast Notification Manager
    // -----------------------------------------------------------------
    function showNotification(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = 'toast-alert';
        toast.style.cssText = `
            position: fixed;
            bottom: 30px;
            left: 30px;
            background: ${type === 'success' ? '#0f766e' : '#1e293b'};
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5);
            z-index: 100000;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            transform: translateY(50px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        `;
        toast.innerHTML = `
            <span>${type === 'success' ? '✅' : 'ℹ️'}</span>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        }, 50);

        setTimeout(() => {
            toast.style.transform = 'translateY(50px)';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }

    // -----------------------------------------------------------------
    // 6. Interactive Bond Calculator
    // -----------------------------------------------------------------
    const calcPrice = document.getElementById('calcPrice');
    const calcDeposit = document.getElementById('calcDeposit');
    const calcInterest = document.getElementById('calcInterest');
    const calcTerm = document.getElementById('calcTerm');

    if (calcPrice && calcDeposit && calcInterest && calcTerm) {
        const repText = document.getElementById('calcMonthlyRepayment');
        const totalText = document.getElementById('calcTotalPaid');
        const minSalaryText = document.getElementById('calcMinSalary');
        const dutyText = document.getElementById('calcTransferDuty');

        const calculateBond = () => {
            const price = parseFloat(calcPrice.value) || 0;
            const depositPct = parseFloat(calcDeposit.value) || 0;
            const interest = parseFloat(calcInterest.value) || 0;
            const term = parseInt(calcTerm.value) || 20;

            const depositVal = price * (depositPct / 100);
            const loanPrincipal = Math.max(0, price - depositVal);

            const r = (interest / 100) / 12;
            const n = term * 12;

            let monthlyPayment = 0;
            if (r > 0) {
                monthlyPayment = loanPrincipal * (r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1);
            } else {
                monthlyPayment = loanPrincipal / n;
            }

            const totalRepayment = monthlyPayment * n;
            const minSalary = monthlyPayment * 3.33;

            let transferDuty = 0;
            if (price > 1100000) {
                if (price <= 1512500) {
                    transferDuty = (price - 1100000) * 0.03;
                } else if (price <= 2117500) {
                    transferDuty = 12375 + (price - 1512500) * 0.05;
                } else if (price <= 2964500) {
                    transferDuty = 42625 + (price - 2117500) * 0.08;
                } else {
                    transferDuty = 110385 + (price - 2964500) * 0.11;
                }
            }

            if (repText) repText.innerText = 'R ' + monthlyPayment.toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (totalText) totalText.innerText = 'R ' + totalRepayment.toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (minSalaryText) minSalaryText.innerText = 'R ' + minSalary.toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (dutyText) dutyText.innerText = 'R ' + transferDuty.toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        [calcDeposit, calcInterest, calcTerm].forEach(input => {
            input.addEventListener('input', calculateBond);
        });

        calculateBond();
    }

    // -----------------------------------------------------------------
    // 7. Interactive Property Image Gallery
    // -----------------------------------------------------------------
    const activeGalleryImg = document.getElementById('activeGalleryImg');
    if (activeGalleryImg) {
        document.querySelectorAll('.gallery-thumb').forEach(thumb => {
            thumb.addEventListener('click', () => {
                document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');

                const newSrc = thumb.getAttribute('data-src');
                activeGalleryImg.setAttribute('src', newSrc);
            });
        });
    }
});
