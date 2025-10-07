/**
 * StateManager Tests
 */

const StateManager = require('../../../assets/js/core/StateManager');
const EventBus = require('../../../assets/js/core/EventBus');

describe('StateManager', () => {
  let stateManager;
  let eventBus;

  beforeEach(() => {
    eventBus = new EventBus();
    stateManager = new StateManager(eventBus);
  });

  afterEach(() => {
    stateManager.destroy();
    eventBus.destroy();
  });

  describe('constructor', () => {
    it('should initialize with default state structure', () => {
      const state = stateManager.getState();

      expect(state).toHaveProperty('settings');
      expect(state).toHaveProperty('themes');
      expect(state).toHaveProperty('backups');
      expect(state).toHaveProperty('ui');
      expect(state).toHaveProperty('preview');
    });

    it('should initialize with empty history', () => {
      const info = stateManager.getHistoryInfo();
      expect(info.length).toBe(0);
      expect(info.index).toBe(-1);
    });

    it('should initialize with no subscribers', () => {
      expect(stateManager.subscribers.size).toBe(0);
    });
  });

  describe('getState()', () => {
    it('should return current state', () => {
      const state = stateManager.getState();
      expect(state).toBeDefined();
      expect(typeof state).toBe('object');
    });

    it('should return deep clone of state', () => {
      const state1 = stateManager.getState();
      const state2 = stateManager.getState();

      expect(state1).not.toBe(state2);
      expect(state1).toEqual(state2);
    });

    it('should prevent mutation of internal state', () => {
      const state = stateManager.getState();
      state.settings.test = 'modified';

      const newState = stateManager.getState();
      expect(newState.settings.test).toBeUndefined();
    });
  });

  describe('get()', () => {
    it('should get value at path', () => {
      stateManager.setState({
        settings: { menu_background: '#1e1e2e' }
      });

      expect(stateManager.get('settings.menu_background')).toBe('#1e1e2e');
    });

    it('should return undefined for non-existent path', () => {
      expect(stateManager.get('non.existent.path')).toBeUndefined();
    });

    it('should handle nested paths', () => {
      stateManager.setState({
        ui: { loading: true }
      });

      expect(stateManager.get('ui.loading')).toBe(true);
    });

    it('should handle root level paths', () => {
      stateManager.setState({
        settings: { test: 'value' }
      });

      const settings = stateManager.get('settings');
      expect(settings).toHaveProperty('test', 'value');
    });
  });

  describe('setState()', () => {
    it('should update state', () => {
      stateManager.setState({
        settings: { menu_background: '#ff5722' }
      });

      expect(stateManager.get('settings.menu_background')).toBe('#ff5722');
    });

    it('should merge updates with existing state', () => {
      stateManager.setState({
        settings: { menu_background: '#1e1e2e' }
      });

      stateManager.setState({
        settings: { menu_text_color: '#ffffff' }
      });

      const settings = stateManager.get('settings');
      expect(settings.menu_background).toBe('#1e1e2e');
      expect(settings.menu_text_color).toBe('#ffffff');
    });

    it('should add to history by default', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.setState({ settings: { test: 2 } });

      const info = stateManager.getHistoryInfo();
      expect(info.length).toBe(2);
    });

    it('should skip history when specified', () => {
      stateManager.setState({ settings: { test: 1 } }, false);

      const info = stateManager.getHistoryInfo();
      expect(info.length).toBe(0);
    });

    it('should notify subscribers', () => {
      const subscriber = jest.fn();
      stateManager.subscribe(subscriber);

      stateManager.setState({ settings: { test: 'value' } });

      expect(subscriber).toHaveBeenCalledWith(
        expect.objectContaining({
          settings: expect.objectContaining({ test: 'value' })
        })
      );
    });

    it('should emit state:changed event', () => {
      const handler = jest.fn();
      eventBus.on('state:changed', handler);

      stateManager.setState({ settings: { test: 'value' } });

      expect(handler).toHaveBeenCalledWith(
        expect.objectContaining({
          data: expect.objectContaining({
            state: expect.any(Object),
            updates: expect.any(Object),
            previousState: expect.any(Object)
          })
        })
      );
    });
  });

  describe('set()', () => {
    it('should set value at path', () => {
      stateManager.set('ui.loading', true);

      expect(stateManager.get('ui.loading')).toBe(true);
    });

    it('should handle nested paths', () => {
      stateManager.set('settings.menu_background', '#ff5722');

      expect(stateManager.get('settings.menu_background')).toBe('#ff5722');
    });

    it('should add to history by default', () => {
      stateManager.set('ui.loading', true);

      const info = stateManager.getHistoryInfo();
      expect(info.length).toBe(1);
    });

    it('should skip history when specified', () => {
      stateManager.set('ui.loading', true, false);

      const info = stateManager.getHistoryInfo();
      expect(info.length).toBe(0);
    });
  });

  describe('subscribe()', () => {
    it('should add subscriber', () => {
      const subscriber = jest.fn();
      stateManager.subscribe(subscriber);

      expect(stateManager.subscribers.size).toBe(1);
    });

    it('should return unsubscribe function', () => {
      const subscriber = jest.fn();
      const unsubscribe = stateManager.subscribe(subscriber);

      expect(typeof unsubscribe).toBe('function');

      unsubscribe();
      expect(stateManager.subscribers.size).toBe(0);
    });

    it('should call subscriber on state change', () => {
      const subscriber = jest.fn();
      stateManager.subscribe(subscriber);

      stateManager.setState({ settings: { test: 'value' } });

      expect(subscriber).toHaveBeenCalled();
    });

    it('should throw error for invalid subscriber', () => {
      expect(() => {
        stateManager.subscribe('not-a-function');
      }).toThrow('[StateManager] Subscriber must be a function');
    });

    it('should handle errors in subscribers', () => {
      const errorSubscriber = jest.fn(() => {
        throw new Error('Test error');
      });
      const normalSubscriber = jest.fn();

      stateManager.subscribe(errorSubscriber);
      stateManager.subscribe(normalSubscriber);

      expect(() => {
        stateManager.setState({ settings: { test: 'value' } });
      }).not.toThrow();

      expect(normalSubscriber).toHaveBeenCalled();
    });
  });

  describe('undo()', () => {
    it('should undo last state change', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.setState({ settings: { test: 2 } });

      stateManager.undo();

      expect(stateManager.get('settings.test')).toBe(1);
    });

    it('should return true on successful undo', () => {
      stateManager.setState({ settings: { test: 1 } });
      const result = stateManager.undo();

      expect(result).toBe(true);
    });

    it('should return false when cannot undo', () => {
      const result = stateManager.undo();
      expect(result).toBe(false);
    });

    it('should notify subscribers', () => {
      const subscriber = jest.fn();
      stateManager.subscribe(subscriber);

      stateManager.setState({ settings: { test: 1 } });
      subscriber.mockClear();

      stateManager.undo();

      expect(subscriber).toHaveBeenCalled();
    });

    it('should emit state:undo event', () => {
      const handler = jest.fn();
      eventBus.on('state:undo', handler);

      stateManager.setState({ settings: { test: 1 } });
      stateManager.undo();

      expect(handler).toHaveBeenCalled();
    });
  });

  describe('redo()', () => {
    it('should redo undone state change', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.setState({ settings: { test: 2 } });
      stateManager.undo();

      stateManager.redo();

      expect(stateManager.get('settings.test')).toBe(2);
    });

    it('should return true on successful redo', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.undo();

      const result = stateManager.redo();
      expect(result).toBe(true);
    });

    it('should return false when cannot redo', () => {
      const result = stateManager.redo();
      expect(result).toBe(false);
    });

    it('should emit state:redo event', () => {
      const handler = jest.fn();
      eventBus.on('state:redo', handler);

      stateManager.setState({ settings: { test: 1 } });
      stateManager.undo();
      stateManager.redo();

      expect(handler).toHaveBeenCalled();
    });
  });

  describe('canUndo()', () => {
    it('should return false initially', () => {
      expect(stateManager.canUndo()).toBe(false);
    });

    it('should return true after state change', () => {
      stateManager.setState({ settings: { test: 1 } });
      expect(stateManager.canUndo()).toBe(true);
    });

    it('should return false after undoing all changes', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.undo();
      expect(stateManager.canUndo()).toBe(false);
    });
  });

  describe('canRedo()', () => {
    it('should return false initially', () => {
      expect(stateManager.canRedo()).toBe(false);
    });

    it('should return true after undo', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.undo();
      expect(stateManager.canRedo()).toBe(true);
    });

    it('should return false after redo', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.undo();
      stateManager.redo();
      expect(stateManager.canRedo()).toBe(false);
    });
  });

  describe('clearHistory()', () => {
    it('should clear history', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.setState({ settings: { test: 2 } });

      stateManager.clearHistory();

      const info = stateManager.getHistoryInfo();
      expect(info.length).toBe(0);
      expect(info.index).toBe(-1);
    });

    it('should prevent undo after clearing', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.clearHistory();

      expect(stateManager.canUndo()).toBe(false);
    });
  });

  describe('reset()', () => {
    it('should reset state to initial values', () => {
      stateManager.setState({ settings: { test: 'value' } });
      stateManager.reset();

      const state = stateManager.getState();
      expect(state.settings).toEqual({});
    });

    it('should add to history by default', () => {
      stateManager.setState({ settings: { test: 'value' } });
      stateManager.reset();

      expect(stateManager.canUndo()).toBe(true);
    });

    it('should skip history when specified', () => {
      stateManager.setState({ settings: { test: 'value' } });
      stateManager.reset(false);

      const info = stateManager.getHistoryInfo();
      expect(info.length).toBe(1); // Only the first setState
    });

    it('should emit state:reset event', () => {
      const handler = jest.fn();
      eventBus.on('state:reset', handler);

      stateManager.reset();

      expect(handler).toHaveBeenCalled();
    });
  });

  describe('getHistoryInfo()', () => {
    it('should return history information', () => {
      const info = stateManager.getHistoryInfo();

      expect(info).toHaveProperty('length');
      expect(info).toHaveProperty('index');
      expect(info).toHaveProperty('canUndo');
      expect(info).toHaveProperty('canRedo');
    });

    it('should reflect current history state', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.setState({ settings: { test: 2 } });

      const info = stateManager.getHistoryInfo();
      expect(info.length).toBe(2);
      expect(info.canUndo).toBe(true);
      expect(info.canRedo).toBe(false);
    });
  });

  describe('setDebug()', () => {
    it('should enable debug mode', () => {
      stateManager.setDebug(true);
      expect(stateManager.debug).toBe(true);
    });

    it('should disable debug mode', () => {
      stateManager.setDebug(true);
      stateManager.setDebug(false);
      expect(stateManager.debug).toBe(false);
    });
  });

  describe('destroy()', () => {
    it('should clear subscribers', () => {
      stateManager.subscribe(jest.fn());
      stateManager.destroy();

      expect(stateManager.subscribers.size).toBe(0);
    });

    it('should clear history', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.destroy();

      expect(stateManager.history).toEqual([]);
      expect(stateManager.historyIndex).toBe(-1);
    });
  });

  describe('deep merge', () => {
    it('should deep merge nested objects', () => {
      stateManager.setState({
        settings: {
          menu: { background: '#1e1e2e' }
        }
      });

      stateManager.setState({
        settings: {
          menu: { text: '#ffffff' }
        }
      });

      const menu = stateManager.get('settings.menu');
      expect(menu.background).toBe('#1e1e2e');
      expect(menu.text).toBe('#ffffff');
    });

    it('should replace arrays instead of merging', () => {
      stateManager.setState({
        themes: ['theme1', 'theme2']
      });

      stateManager.setState({
        themes: ['theme3']
      });

      expect(stateManager.get('themes')).toEqual(['theme3']);
    });
  });

  describe('history management', () => {
    it('should limit history size', () => {
      const maxHistory = stateManager.maxHistory;

      for (let i = 0; i < maxHistory + 10; i++) {
        stateManager.setState({ settings: { test: i } });
      }

      const info = stateManager.getHistoryInfo();
      expect(info.length).toBeLessThanOrEqual(maxHistory);
    });

    it('should clear redo history on new state change', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.setState({ settings: { test: 2 } });
      stateManager.undo();

      expect(stateManager.canRedo()).toBe(true);

      stateManager.setState({ settings: { test: 3 } });

      expect(stateManager.canRedo()).toBe(false);
    });
  });

  describe('integration scenarios', () => {
    it('should support complex state updates', () => {
      stateManager.setState({
        settings: { menu_background: '#1e1e2e' },
        ui: { loading: true }
      });

      stateManager.setState({
        settings: { menu_text_color: '#ffffff' },
        ui: { loading: false }
      });

      const state = stateManager.getState();
      expect(state.settings.menu_background).toBe('#1e1e2e');
      expect(state.settings.menu_text_color).toBe('#ffffff');
      expect(state.ui.loading).toBe(false);
    });

    it('should support undo/redo workflow', () => {
      stateManager.setState({ settings: { test: 1 } });
      stateManager.setState({ settings: { test: 2 } });
      stateManager.setState({ settings: { test: 3 } });

      stateManager.undo();
      expect(stateManager.get('settings.test')).toBe(2);

      stateManager.undo();
      expect(stateManager.get('settings.test')).toBe(1);

      stateManager.redo();
      expect(stateManager.get('settings.test')).toBe(2);

      stateManager.redo();
      expect(stateManager.get('settings.test')).toBe(3);
    });

    it('should support reactive UI updates', () => {
      const uiUpdates = [];

      stateManager.subscribe((state) => {
        uiUpdates.push(state.ui.loading);
      });

      stateManager.set('ui.loading', true);
      stateManager.set('ui.loading', false);

      expect(uiUpdates).toEqual([true, false]);
    });
  });
});
