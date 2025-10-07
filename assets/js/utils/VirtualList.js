/**
 * VirtualList Utility
 * 
 * Efficiently renders large lists by only rendering visible items.
 * Uses viewport calculation and scroll event handling with throttling.
 * 
 * @class VirtualList
 */
class VirtualList {
    constructor(container, options = {}) {
        if (!container) {
            throw new Error('Container element is required');
        }

        this.container = container;
        this.options = {
            itemHeight: 50, // Default item height in pixels
            buffer: 5, // Number of items to render outside viewport
            throttleDelay: 16, // ~60fps
            estimatedItemCount: 100,
            renderItem: null, // Function to render each item
            onScroll: null, // Scroll callback
            ...options
        };

        // State
        this.items = [];
        this.visibleRange = { start: 0, end: 0 };
        this.scrollTop = 0;
        this.containerHeight = 0;
        this.totalHeight = 0;

        // DOM elements
        this.viewport = null;
        this.content = null;
        this.spacerTop = null;
        this.spacerBottom = null;

        // Throttled scroll handler
        this.throttledScrollHandler = this.throttle(
            this.handleScroll.bind(this),
            this.options.throttleDelay
        );

        // Resize observer
        this.resizeObserver = null;

        // Initialize
        this.init();
    }

    /**
     * Initialize virtual list
     */
    init() {
        // Create viewport structure
        this.createViewport();

        // Setup event listeners
        this.setupEventListeners();

        // Initial render
        this.update();
    }

    /**
     * Create viewport structure
     */
    createViewport() {
        // Clear container
        this.container.innerHTML = '';

        // Create viewport wrapper
        this.viewport = document.createElement('div');
        this.viewport.className = 'mas-virtual-list-viewport';
        this.viewport.style.cssText = `
            position: relative;
            overflow-y: auto;
            overflow-x: hidden;
            height: 100%;
            width: 100%;
        `;

        // Create content container
        this.content = document.createElement('div');
        this.content.className = 'mas-virtual-list-content';
        this.content.style.cssText = `
            position: relative;
            width: 100%;
        `;

        // Create spacers for virtual scrolling
        this.spacerTop = document.createElement('div');
        this.spacerTop.className = 'mas-virtual-list-spacer-top';
        this.spacerTop.style.cssText = 'height: 0px;';

        this.spacerBottom = document.createElement('div');
        this.spacerBottom.className = 'mas-virtual-list-spacer-bottom';
        this.spacerBottom.style.cssText = 'height: 0px;';

        // Assemble structure
        this.content.appendChild(this.spacerTop);
        this.content.appendChild(this.spacerBottom);
        this.viewport.appendChild(this.content);
        this.container.appendChild(this.viewport);

        // Store container height
        this.containerHeight = this.viewport.clientHeight;
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Scroll event with throttling
        this.viewport.addEventListener('scroll', this.throttledScrollHandler, { passive: true });

        // Resize observer for container size changes
        if ('ResizeObserver' in window) {
            this.resizeObserver = new ResizeObserver(() => {
                this.containerHeight = this.viewport.clientHeight;
                this.update();
            });
            this.resizeObserver.observe(this.viewport);
        }
    }

    /**
     * Handle scroll event
     */
    handleScroll() {
        this.scrollTop = this.viewport.scrollTop;
        this.update();

        // Call user scroll callback
        if (this.options.onScroll) {
            this.options.onScroll({
                scrollTop: this.scrollTop,
                visibleRange: this.visibleRange,
                scrollPercentage: (this.scrollTop / (this.totalHeight - this.containerHeight)) * 100
            });
        }
    }

    /**
     * Calculate visible range
     * 
     * @returns {Object} Visible range {start, end}
     */
    calculateVisibleRange() {
        const itemHeight = this.options.itemHeight;
        const buffer = this.options.buffer;

        // Calculate visible items
        const startIndex = Math.floor(this.scrollTop / itemHeight);
        const endIndex = Math.ceil((this.scrollTop + this.containerHeight) / itemHeight);

        // Add buffer
        const start = Math.max(0, startIndex - buffer);
        const end = Math.min(this.items.length, endIndex + buffer);

        return { start, end };
    }

    /**
     * Update visible items
     */
    update() {
        if (this.items.length === 0) {
            return;
        }

        // Calculate new visible range
        const newRange = this.calculateVisibleRange();

        // Check if range changed
        if (newRange.start === this.visibleRange.start && newRange.end === this.visibleRange.end) {
            return;
        }

        this.visibleRange = newRange;

        // Render visible items
        this.render();
    }

    /**
     * Render visible items
     */
    render() {
        const { start, end } = this.visibleRange;
        const itemHeight = this.options.itemHeight;

        // Calculate spacer heights
        const topHeight = start * itemHeight;
        const bottomHeight = (this.items.length - end) * itemHeight;

        // Update spacers
        this.spacerTop.style.height = `${topHeight}px`;
        this.spacerBottom.style.height = `${bottomHeight}px`;

        // Remove existing items
        const existingItems = this.content.querySelectorAll('.mas-virtual-list-item');
        existingItems.forEach(item => item.remove());

        // Render visible items
        const fragment = document.createDocumentFragment();

        for (let i = start; i < end; i++) {
            const item = this.items[i];
            const itemElement = this.renderItem(item, i);

            if (itemElement) {
                itemElement.className = 'mas-virtual-list-item';
                itemElement.style.cssText = `
                    position: absolute;
                    top: ${i * itemHeight}px;
                    left: 0;
                    right: 0;
                    height: ${itemHeight}px;
                `;
                itemElement.dataset.index = i;
                fragment.appendChild(itemElement);
            }
        }

        // Insert items after top spacer
        this.spacerTop.after(fragment);
    }

    /**
     * Render a single item
     * 
     * @param {*} item - Item data
     * @param {number} index - Item index
     * @returns {HTMLElement} Rendered item element
     */
    renderItem(item, index) {
        if (this.options.renderItem) {
            return this.options.renderItem(item, index);
        }

        // Default renderer
        const div = document.createElement('div');
        div.textContent = typeof item === 'object' ? JSON.stringify(item) : item;
        return div;
    }

    /**
     * Set items
     * 
     * @param {Array} items - Array of items to display
     */
    setItems(items) {
        this.items = items || [];
        this.totalHeight = this.items.length * this.options.itemHeight;

        // Reset scroll position
        this.scrollTop = 0;
        this.viewport.scrollTop = 0;

        // Update visible range
        this.visibleRange = { start: 0, end: 0 };

        // Render
        this.update();
    }

    /**
     * Add items
     * 
     * @param {Array} items - Items to add
     * @param {boolean} prepend - Whether to prepend items
     */
    addItems(items, prepend = false) {
        if (!items || items.length === 0) {
            return;
        }

        if (prepend) {
            this.items = [...items, ...this.items];
            // Adjust scroll position to maintain visual position
            const addedHeight = items.length * this.options.itemHeight;
            this.viewport.scrollTop += addedHeight;
        } else {
            this.items = [...this.items, ...items];
        }

        this.totalHeight = this.items.length * this.options.itemHeight;
        this.update();
    }

    /**
     * Remove item by index
     * 
     * @param {number} index - Item index
     */
    removeItem(index) {
        if (index < 0 || index >= this.items.length) {
            return;
        }

        this.items.splice(index, 1);
        this.totalHeight = this.items.length * this.options.itemHeight;
        this.update();
    }

    /**
     * Update item by index
     * 
     * @param {number} index - Item index
     * @param {*} newItem - New item data
     */
    updateItem(index, newItem) {
        if (index < 0 || index >= this.items.length) {
            return;
        }

        this.items[index] = newItem;

        // Re-render if item is visible
        if (index >= this.visibleRange.start && index < this.visibleRange.end) {
            this.render();
        }
    }

    /**
     * Get item by index
     * 
     * @param {number} index - Item index
     * @returns {*} Item data
     */
    getItem(index) {
        return this.items[index];
    }

    /**
     * Get all items
     * 
     * @returns {Array} All items
     */
    getItems() {
        return this.items;
    }

    /**
     * Get visible items
     * 
     * @returns {Array} Visible items
     */
    getVisibleItems() {
        const { start, end } = this.visibleRange;
        return this.items.slice(start, end);
    }

    /**
     * Scroll to index
     * 
     * @param {number} index - Item index
     * @param {string} behavior - Scroll behavior ('auto' or 'smooth')
     */
    scrollToIndex(index, behavior = 'auto') {
        if (index < 0 || index >= this.items.length) {
            return;
        }

        const scrollTop = index * this.options.itemHeight;
        this.viewport.scrollTo({
            top: scrollTop,
            behavior
        });
    }

    /**
     * Scroll to top
     * 
     * @param {string} behavior - Scroll behavior
     */
    scrollToTop(behavior = 'auto') {
        this.viewport.scrollTo({
            top: 0,
            behavior
        });
    }

    /**
     * Scroll to bottom
     * 
     * @param {string} behavior - Scroll behavior
     */
    scrollToBottom(behavior = 'auto') {
        this.viewport.scrollTo({
            top: this.totalHeight,
            behavior
        });
    }

    /**
     * Refresh/re-render
     */
    refresh() {
        this.update();
    }

    /**
     * Set item height
     * 
     * @param {number} height - New item height
     */
    setItemHeight(height) {
        this.options.itemHeight = height;
        this.totalHeight = this.items.length * height;
        this.update();
    }

    /**
     * Throttle function
     * 
     * @param {Function} func - Function to throttle
     * @param {number} delay - Delay in milliseconds
     * @returns {Function} Throttled function
     */
    throttle(func, delay) {
        let lastCall = 0;
        let timeoutId = null;

        return function (...args) {
            const now = Date.now();
            const timeSinceLastCall = now - lastCall;

            if (timeSinceLastCall >= delay) {
                lastCall = now;
                func.apply(this, args);
            } else {
                // Schedule call for later
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }
                timeoutId = setTimeout(() => {
                    lastCall = Date.now();
                    func.apply(this, args);
                }, delay - timeSinceLastCall);
            }
        };
    }

    /**
     * Destroy virtual list
     */
    destroy() {
        // Remove event listeners
        if (this.viewport) {
            this.viewport.removeEventListener('scroll', this.throttledScrollHandler);
        }

        // Disconnect resize observer
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
            this.resizeObserver = null;
        }

        // Clear container
        if (this.container) {
            this.container.innerHTML = '';
        }

        // Clear references
        this.items = [];
        this.viewport = null;
        this.content = null;
        this.spacerTop = null;
        this.spacerBottom = null;
    }

    /**
     * Get statistics
     * 
     * @returns {Object} Statistics
     */
    getStats() {
        return {
            totalItems: this.items.length,
            visibleItems: this.visibleRange.end - this.visibleRange.start,
            visibleRange: this.visibleRange,
            scrollTop: this.scrollTop,
            containerHeight: this.containerHeight,
            totalHeight: this.totalHeight,
            scrollPercentage: (this.scrollTop / (this.totalHeight - this.containerHeight)) * 100
        };
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VirtualList;
}

// Make available globally
if (typeof window !== 'undefined') {
    window.VirtualList = VirtualList;
}
