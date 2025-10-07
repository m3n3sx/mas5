/**
 * EventBus Tests
 */

const EventBus = require('../../../assets/js/core/EventBus');

describe('EventBus', () => {
  let eventBus;

  beforeEach(() => {
    eventBus = new EventBus();
  });

  afterEach(() => {
    eventBus.destroy();
  });

  describe('constructor', () => {
    it('should initialize with empty listeners map', () => {
      expect(eventBus.listeners.size).toBe(0);
    });

    it('should initialize with debug mode disabled', () => {
      expect(eventBus.debug).toBe(false);
    });

    it('should initialize with empty event history', () => {
      expect(eventBus.eventHistory).toEqual([]);
    });
  });

  describe('on()', () => {
    it('should subscribe to an event', () => {
      const callback = jest.fn();
      eventBus.on('test:event', callback);

      expect(eventBus.getListenerCount('test:event')).toBe(1);
    });

    it('should return unsubscribe function', () => {
      const callback = jest.fn();
      const unsubscribe = eventBus.on('test:event', callback);

      expect(typeof unsubscribe).toBe('function');
      
      unsubscribe();
      expect(eventBus.getListenerCount('test:event')).toBe(0);
    });

    it('should allow multiple subscribers to same event', () => {
      const callback1 = jest.fn();
      const callback2 = jest.fn();

      eventBus.on('test:event', callback1);
      eventBus.on('test:event', callback2);

      expect(eventBus.getListenerCount('test:event')).toBe(2);
    });

    it('should support event namespacing', () => {
      const callback = jest.fn();
      eventBus.on('settings:changed', callback);

      expect(eventBus.getListenerCount('settings:changed')).toBe(1);
    });

    it('should throw error for invalid event name', () => {
      expect(() => {
        eventBus.on('', jest.fn());
      }).toThrow('[EventBus] Event name must be a non-empty string');

      expect(() => {
        eventBus.on(null, jest.fn());
      }).toThrow('[EventBus] Event name must be a non-empty string');
    });

    it('should throw error for invalid callback', () => {
      expect(() => {
        eventBus.on('test:event', 'not-a-function');
      }).toThrow('[EventBus] Callback must be a function');

      expect(() => {
        eventBus.on('test:event', null);
      }).toThrow('[EventBus] Callback must be a function');
    });

    it('should support context binding', () => {
      const context = { value: 42 };
      let capturedContext;

      eventBus.on('test:event', function() {
        capturedContext = this;
      }, context);

      eventBus.emit('test:event');

      expect(capturedContext).toBe(context);
    });
  });

  describe('once()', () => {
    it('should subscribe to event only once', () => {
      const callback = jest.fn();
      eventBus.once('test:event', callback);

      eventBus.emit('test:event');
      eventBus.emit('test:event');

      expect(callback).toHaveBeenCalledTimes(1);
    });

    it('should auto-unsubscribe after first call', () => {
      const callback = jest.fn();
      eventBus.once('test:event', callback);

      eventBus.emit('test:event');
      
      expect(eventBus.getListenerCount('test:event')).toBe(0);
    });

    it('should return unsubscribe function', () => {
      const callback = jest.fn();
      const unsubscribe = eventBus.once('test:event', callback);

      unsubscribe();
      eventBus.emit('test:event');

      expect(callback).not.toHaveBeenCalled();
    });
  });

  describe('off()', () => {
    it('should unsubscribe from event', () => {
      const callback = jest.fn();
      eventBus.on('test:event', callback);
      eventBus.off('test:event', callback);

      expect(eventBus.getListenerCount('test:event')).toBe(0);
    });

    it('should only remove specified callback', () => {
      const callback1 = jest.fn();
      const callback2 = jest.fn();

      eventBus.on('test:event', callback1);
      eventBus.on('test:event', callback2);
      eventBus.off('test:event', callback1);

      expect(eventBus.getListenerCount('test:event')).toBe(1);
      
      eventBus.emit('test:event');
      expect(callback1).not.toHaveBeenCalled();
      expect(callback2).toHaveBeenCalled();
    });

    it('should handle unsubscribing from non-existent event', () => {
      expect(() => {
        eventBus.off('non:existent', jest.fn());
      }).not.toThrow();
    });

    it('should clean up empty listener arrays', () => {
      const callback = jest.fn();
      eventBus.on('test:event', callback);
      eventBus.off('test:event', callback);

      expect(eventBus.listeners.has('test:event')).toBe(false);
    });
  });

  describe('emit()', () => {
    it('should call all subscribers', () => {
      const callback1 = jest.fn();
      const callback2 = jest.fn();

      eventBus.on('test:event', callback1);
      eventBus.on('test:event', callback2);
      eventBus.emit('test:event');

      expect(callback1).toHaveBeenCalledTimes(1);
      expect(callback2).toHaveBeenCalledTimes(1);
    });

    it('should pass event object to callbacks', () => {
      const callback = jest.fn();
      const data = { value: 42 };

      eventBus.on('test:event', callback);
      eventBus.emit('test:event', data);

      expect(callback).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'test:event',
          data,
          timestamp: expect.any(Number)
        })
      );
    });

    it('should handle events with no subscribers', () => {
      expect(() => {
        eventBus.emit('non:existent');
      }).not.toThrow();
    });

    it('should add event to history', () => {
      eventBus.emit('test:event', { value: 42 });

      const history = eventBus.getHistory();
      expect(history).toHaveLength(1);
      expect(history[0].type).toBe('test:event');
    });

    it('should handle errors in callbacks', () => {
      const errorCallback = jest.fn(() => {
        throw new Error('Test error');
      });
      const normalCallback = jest.fn();

      eventBus.on('test:event', errorCallback);
      eventBus.on('test:event', normalCallback);

      expect(() => {
        eventBus.emit('test:event');
      }).not.toThrow();

      expect(normalCallback).toHaveBeenCalled();
    });

    it('should emit error event on callback error', () => {
      const errorCallback = jest.fn(() => {
        throw new Error('Test error');
      });
      const errorHandler = jest.fn();

      eventBus.on('test:event', errorCallback);
      eventBus.on('eventbus:error', errorHandler);

      eventBus.emit('test:event');

      expect(errorHandler).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'eventbus:error',
          data: expect.objectContaining({
            originalEvent: 'test:event',
            error: expect.any(Error)
          })
        })
      );
    });
  });

  describe('clear()', () => {
    it('should clear listeners for specific event', () => {
      eventBus.on('event1', jest.fn());
      eventBus.on('event2', jest.fn());

      eventBus.clear('event1');

      expect(eventBus.getListenerCount('event1')).toBe(0);
      expect(eventBus.getListenerCount('event2')).toBe(1);
    });

    it('should clear all listeners when no event specified', () => {
      eventBus.on('event1', jest.fn());
      eventBus.on('event2', jest.fn());

      eventBus.clear();

      expect(eventBus.getListenerCount('event1')).toBe(0);
      expect(eventBus.getListenerCount('event2')).toBe(0);
      expect(eventBus.listeners.size).toBe(0);
    });
  });

  describe('getListenerCount()', () => {
    it('should return correct listener count', () => {
      eventBus.on('test:event', jest.fn());
      eventBus.on('test:event', jest.fn());

      expect(eventBus.getListenerCount('test:event')).toBe(2);
    });

    it('should return 0 for non-existent event', () => {
      expect(eventBus.getListenerCount('non:existent')).toBe(0);
    });
  });

  describe('getEventNames()', () => {
    it('should return array of registered event names', () => {
      eventBus.on('event1', jest.fn());
      eventBus.on('event2', jest.fn());

      const names = eventBus.getEventNames();
      expect(names).toContain('event1');
      expect(names).toContain('event2');
      expect(names).toHaveLength(2);
    });

    it('should return empty array when no events registered', () => {
      expect(eventBus.getEventNames()).toEqual([]);
    });
  });

  describe('setDebug()', () => {
    it('should enable debug mode', () => {
      eventBus.setDebug(true);
      expect(eventBus.debug).toBe(true);
    });

    it('should disable debug mode', () => {
      eventBus.setDebug(true);
      eventBus.setDebug(false);
      expect(eventBus.debug).toBe(false);
    });
  });

  describe('getHistory()', () => {
    it('should return event history', () => {
      eventBus.emit('event1', { value: 1 });
      eventBus.emit('event2', { value: 2 });

      const history = eventBus.getHistory();
      expect(history).toHaveLength(2);
      expect(history[0].type).toBe('event1');
      expect(history[1].type).toBe('event2');
    });

    it('should return copy of history array', () => {
      eventBus.emit('test:event');
      const history1 = eventBus.getHistory();
      const history2 = eventBus.getHistory();

      expect(history1).not.toBe(history2);
      expect(history1).toEqual(history2);
    });

    it('should limit history size', () => {
      // Emit more events than maxHistorySize
      for (let i = 0; i < 150; i++) {
        eventBus.emit('test:event', { index: i });
      }

      const history = eventBus.getHistory();
      expect(history.length).toBeLessThanOrEqual(eventBus.maxHistorySize);
    });
  });

  describe('clearHistory()', () => {
    it('should clear event history', () => {
      eventBus.emit('test:event');
      eventBus.clearHistory();

      expect(eventBus.getHistory()).toEqual([]);
    });
  });

  describe('destroy()', () => {
    it('should clear all listeners', () => {
      eventBus.on('event1', jest.fn());
      eventBus.on('event2', jest.fn());

      eventBus.destroy();

      expect(eventBus.listeners.size).toBe(0);
    });

    it('should clear event history', () => {
      eventBus.emit('test:event');
      eventBus.destroy();

      expect(eventBus.eventHistory).toEqual([]);
    });
  });

  describe('memory leak prevention', () => {
    it('should clean up empty listener arrays', () => {
      const callback = jest.fn();
      eventBus.on('test:event', callback);
      eventBus.off('test:event', callback);

      expect(eventBus.listeners.has('test:event')).toBe(false);
    });

    it('should limit history size', () => {
      const maxSize = eventBus.maxHistorySize;
      
      for (let i = 0; i < maxSize + 50; i++) {
        eventBus.emit('test:event');
      }

      expect(eventBus.eventHistory.length).toBe(maxSize);
    });
  });

  describe('integration scenarios', () => {
    it('should support complex event flow', () => {
      const results = [];

      eventBus.on('step1', () => {
        results.push('step1');
        eventBus.emit('step2');
      });

      eventBus.on('step2', () => {
        results.push('step2');
        eventBus.emit('step3');
      });

      eventBus.on('step3', () => {
        results.push('step3');
      });

      eventBus.emit('step1');

      expect(results).toEqual(['step1', 'step2', 'step3']);
    });

    it('should handle rapid event emissions', () => {
      const callback = jest.fn();
      eventBus.on('test:event', callback);

      for (let i = 0; i < 1000; i++) {
        eventBus.emit('test:event');
      }

      expect(callback).toHaveBeenCalledTimes(1000);
    });

    it('should support event-driven state machine', () => {
      let state = 'idle';

      eventBus.on('start', () => {
        state = 'running';
        eventBus.emit('running');
      });

      eventBus.on('stop', () => {
        state = 'stopped';
        eventBus.emit('stopped');
      });

      eventBus.emit('start');
      expect(state).toBe('running');

      eventBus.emit('stop');
      expect(state).toBe('stopped');
    });
  });
});
