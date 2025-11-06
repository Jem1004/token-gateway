/**
 * Token Countdown Timer
 *
 * Real-time countdown timer for token rotation
 * Provides visual feedback and automatic refresh
 */

class TokenCountdown {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Countdown container not found');
            return;
        }

        this.options = {
            refreshInterval: 1000, // 1 second
            warningThreshold: 300, // 5 minutes in seconds
            criticalThreshold: 60, // 1 minute in seconds
            apiEndpoint: 'api_token_info.php',
            ...options
        };

        this.tokenData = null;
        this.countdownInterval = null;
        this.refreshInterval = null;

        this.init();
    }

    init() {
        this.bindEvents();
        this.startCountdown();
        this.startAutoRefresh();
    }

    bindEvents() {
        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseCountdown();
            } else {
                this.resumeCountdown();
                this.refreshTokenData();
            }
        });

        // Handle page focus
        window.addEventListener('focus', () => {
            this.refreshTokenData();
        });

        // Handle manual refresh button
        const refreshBtn = document.getElementById('refreshTokenBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshTokenData();
            });
        }
    }

    async refreshTokenData() {
        try {
            const response = await fetch(this.options.apiEndpoint);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                this.tokenData = data;
                this.updateDisplay();
            } else {
                console.error('API Error:', data.message);
                this.showError('Gagal memuat data token');
            }
        } catch (error) {
            console.error('Refresh Error:', error);
            this.showError('Koneksi error. Coba lagi nanti.');
        }
    }

    startCountdown() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }

        this.countdownInterval = setInterval(() => {
            this.updateCountdown();
        }, this.options.refreshInterval);

        // Initial update
        this.updateCountdown();
    }

    pauseCountdown() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }
    }

    resumeCountdown() {
        this.startCountdown();
    }

    startAutoRefresh() {
        // Refresh token data every 30 seconds
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }

        this.refreshInterval = setInterval(() => {
            this.refreshTokenData();
        }, 30000); // 30 seconds
    }

    updateCountdown() {
        if (!this.tokenData || !this.tokenData.next_rotation_time) {
            return;
        }

        const now = new Date().getTime();
        const nextRotation = new Date(this.tokenData.next_rotation_time).getTime();
        const timeUntil = nextRotation - now;

        if (timeUntil <= 0) {
            this.handleExpired();
            return;
        }

        this.renderCountdown(timeUntil);
    }

    renderCountdown(seconds) {
        const days = Math.floor(seconds / (24 * 60 * 60));
        const hours = Math.floor((seconds % (24 * 60 * 60)) / (60 * 60));
        const minutes = Math.floor((seconds % (60 * 60)) / 60);
        const secs = Math.floor(seconds % 60);

        // Update countdown display
        this.updateCountdownDisplay({ days, hours, minutes, seconds: secs });

        // Update progress bar
        this.updateProgressBar(seconds);

        // Update status
        this.updateStatus(seconds);
    }

    updateCountdownDisplay(time) {
        const elements = {
            days: document.getElementById('countdown-days'),
            hours: document.getElementById('countdown-hours'),
            minutes: document.getElementById('countdown-minutes'),
            seconds: document.getElementById('countdown-seconds')
        };

        if (elements.days) {
            elements.days.textContent = String(time.days).padStart(2, '0');
        }
        if (elements.hours) {
            elements.hours.textContent = String(time.hours).padStart(2, '0');
        }
        if (elements.minutes) {
            elements.minutes.textContent = String(time.minutes).padStart(2, '0');
        }
        if (elements.seconds) {
            elements.seconds.textContent = String(time.seconds).padStart(2, '0');
        }
    }

    updateProgressBar(seconds) {
        if (!this.tokenData || !this.tokenData.rotation_interval) {
            return;
        }

        const totalSeconds = this.tokenData.rotation_interval * 60;
        const remainingPercentage = (seconds / totalSeconds) * 100;

        const progressBar = document.getElementById('countdownProgress');
        if (progressBar) {
            progressBar.style.width = `${remainingPercentage}%`;
        }
    }

    updateStatus(seconds) {
        const statusElement = document.getElementById('countdownStatus');
        if (!statusElement) {
            return;
        }

        let statusClass = 'countdown-status--active';
        let statusText = 'Aktif';

        if (seconds <= this.options.criticalThreshold) {
            statusClass = 'countdown-status--expired';
            statusText = 'Kadaluarsa';
        } else if (seconds <= this.options.warningThreshold) {
            statusClass = 'countdown-status--warning';
            statusText = 'Peringatan';
        }

        statusElement.className = `countdown-status ${statusClass}`;
        statusElement.textContent = statusText;
    }

    handleExpired() {
        this.updateStatus(0);
        this.updateCountdownDisplay({ days: 0, hours: 0, minutes: 0, seconds: 0 });
        this.updateProgressBar(0);

        // Auto refresh expired token
        setTimeout(() => {
            this.refreshTokenData();
        }, 5000);

        // Show notification if browser supports it
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Token Kadaluarsa', {
                body: 'Token telah kadaluarsa. Memuat token baru...',
                icon: '/favicon.ico'
            });
        }
    }

    updateDisplay() {
        if (!this.tokenData) {
            return;
        }

        // Update current token
        const tokenElement = document.getElementById('currentToken');
        if (tokenElement && this.tokenData.current_token) {
            tokenElement.textContent = this.tokenData.current_token;
        }

        // Update info fields
        const infoElements = {
            'lastRotationTime': this.tokenData.last_rotation_time,
            'nextRotationTime': this.tokenData.next_rotation_time,
            'rotationInterval': this.tokenData.rotation_interval ? `${this.tokenData.rotation_interval} menit` : 'N/A',
            'autoRotationStatus': this.tokenData.auto_rotation_enabled ? 'Aktif' : 'Non-aktif'
        };

        Object.keys(infoElements).forEach(key => {
            const element = document.getElementById(key);
            if (element && infoElements[key]) {
                element.textContent = infoElements[key];
            }
        });

        // Update initial countdown
        if (this.tokenData.time_until_rotation) {
            this.renderCountdown(this.tokenData.time_until_rotation);
        }
    }

    showError(message) {
        const errorElement = document.getElementById('countdownError');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    destroy() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Request notification permission
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Initialize countdown if container exists
    if (document.getElementById('countdownContainer')) {
        window.tokenCountdown = new TokenCountdown('countdownContainer');
    }
});