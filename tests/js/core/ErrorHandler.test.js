/**
 * ErrorHandler Tests
 */

const { ErrorHandler, MASError, MASAPIError, MASValidationError } = require('../../../assets/js/core/ErrorHandler');

describe('MASError', () => {
  it('should create error with message and code', () => {
    const error = new MASError('Test error', 'test_code');

    expect(error.message).toBe('Test error');
    expect(error.code).toBe('test_code');
    expect(error.name).toBe('MASError');
  });

  it('should include timestamp', () => {
    const error = new MASError('Test error');
    expect(error.timestamp).toBeDefined();
    expect(typeof error.timestamp).toBe('number');
  });

  it('should serialize to JSON', () => {
    const error = new MASError('Test error', 'test_code', { extra: 'data' });
    const json = error.toJSON();

    expect(json).toHaveProperty('name');
    expect(json).toHaveProperty('message');
    expect(json).toHaveProperty('code');
    expect(json).toHaveProperty('context');
    expect(json).toHaveProperty('timestamp');
  });
});

describe('MASAPIError', () => {
  it('should create API error with status', () => {
    const error = new MASAPIError('API failed', 'api_error', 500);

    expect(error.message).toBe('API failed');
    expect(error.status).toBe(500);
    expect(error.name).toBe('MASAPIError');
  });

  it('should detect network errors', () => {
    const error = new MASAPIError('Network error', 'network_error', 0);
    expect(error.isNetworkError()).toBe(true);
  });

  it('should detect auth errors', () => {
    const error401 = new MASAPIError('Unauthorized', 'auth_error', 401);
    const error403 = new MASAPIError('Forbidden', 'auth_error', 403);

    expect(error401.isAuthError()).toBe(true);
    expect(error403.isAuthError()).toBe(true);
  });

  it('should detect validation errors', () => {
    const error = new MASAPIError('Validation failed', 'validation_failed', 400);
    expect(error.isValidationError()).toBe(true);
  });

  it('should detect server errors', () => {
    const error500 = new MASAPIError('Server error', 'server_error', 500);
    const error503 = new MASAPIError('Service unavailable', 'server_error', 503);

    expect(error500.isServerError()).toBe(true);
    expect(error503.isServerError()).toBe(true);
  });

  it('should determine if retryable', () => {
    const networkError = new MASAPIError('Network error', 'network_error', 0);
    const serverError = new MASAPIError('Server error', 'server_error', 500);
    const authError = new MASAPIError('Unauthorized', 'auth_error', 401);

    expect(networkError.isRetryable()).toBe(true);
    expect(serverError.isRetryable()).toBe(true);
    expect(authError.isRetryable()).toBe(false);
  });
});

describe('MASValidationError', () => {
  it('should create validation error with field errors', () => {
    const errors = {
      field1: 'Error 1',
      field2: 'Error 2'
    };

    const error = new MASValidationError('Validation failed', errors);

    expect(error.message).toBe('Validation failed');
    expect(error.errors).toEqual(errors);
    expect(error.name).toBe('MASValidationError');
  });

  it('should get field errors', () => {
    const errors = { field1: 'Error 1' };
    const error = new MASValidationError('Validation failed', errors);

    expect(error.getFieldErrors()).toEqual(errors);
  });

  it('should check if field has error', () => {
    const errors = { field1: 'Error 1' };
    const error = new MASValidationError('Validation failed', errors);

    expect(error.hasFieldError('field1')).toBe(true);
    expect(error.hasFieldError('field2')).toBe(false);
  });

  it('should get specific field error', () => {
    const errors = { field1: 'Error 1' };
    const error = new MASValidationError('Validation failed', errors);

    expect(error.getFieldError('field1')).toBe('Error 1');
    expect(error.getFieldError('field2')).toBeNull();
  });
});

describe('ErrorHandler', () => {
  let errorHandler;

  beforeEach(() => {
    errorHandler = new ErrorHandler({ debug: false });
  });

  describe('constructor', () => {
    it('should initialize with default config', () => {
      expect(errorHandler.config).toBeDefined();
      expect(errorHandler.config.maxRetries).toBe(3);
    });

    it('should initialize with empty error history', () => {
      expect(errorHandler.errorHistory).toEqual([]);
    });
  });

  describe('handle()', () => {
    it('should handle generic error', () => {
      const error = new Error('Test error');
      const result = errorHandler.handle(error);

      expect(result.error).toBeInstanceOf(MASError);
      expect(result.strategy).toBeDefined();
      expect(result.userMessage).toBeDefined();
      expect(result.actions).toBeInstanceOf(Array);
    });

    it('should add error to history', () => {
      const error = new Error('Test error');
      errorHandler.handle(error);

      expect(errorHandler.errorHistory.length).toBe(1);
    });

    it('should determine retry strategy for network errors', () => {
      const error = new MASAPIError('Network error', 'network_error', 0);
      const result = errorHandler.handle(error);

      expect(result.strategy).toBe('retry');
    });

    it('should determine reauth strategy for auth errors', () => {
      const error = new MASAPIError('Unauthorized', 'auth_error', 401);
      const result = errorHandler.handle(error);

      expect(result.strategy).toBe('reauth');
    });

    it('should determine validate strategy for validation errors', () => {
      const error = new MASValidationError('Validation failed', {});
      const result = errorHandler.handle(error);

      expect(result.strategy).toBe('validate');
    });
  });

  describe('normalizeError()', () => {
    it('should keep MASError as is', () => {
      const error = new MASError('Test error');
      const normalized = errorHandler.normalizeError(error);

      expect(normalized).toBe(error);
    });

    it('should convert API error', () => {
      const error = {
        message: 'API failed',
        status: 500,
        code: 'api_error'
      };

      const normalized = errorHandler.normalizeError(error);

      expect(normalized).toBeInstanceOf(MASAPIError);
      expect(normalized.status).toBe(500);
    });

    it('should convert validation error', () => {
      const error = {
        message: 'Validation failed',
        errors: { field1: 'Error 1' }
      };

      const normalized = errorHandler.normalizeError(error);

      expect(normalized).toBeInstanceOf(MASValidationError);
      expect(normalized.errors).toEqual({ field1: 'Error 1' });
    });

    it('should convert generic error', () => {
      const error = new Error('Generic error');
      const normalized = errorHandler.normalizeError(error);

      expect(normalized).toBeInstanceOf(MASError);
      expect(normalized.message).toBe('Generic error');
    });
  });

  describe('getUserMessage()', () => {
    it('should return user-friendly message for network error', () => {
      const error = new MASError('Network error', 'network_error');
      const message = errorHandler.getUserMessage(error);

      expect(message).toContain('Network error');
    });

    it('should return user-friendly message for auth error', () => {
      const error = new MASError('Forbidden', 'rest_forbidden');
      const message = errorHandler.getUserMessage(error);

      expect(message).toContain('permission');
    });

    it('should return default message for unknown error', () => {
      const error = new MASError('Unknown', 'unknown_code');
      const message = errorHandler.getUserMessage(error);

      expect(message).toBeDefined();
    });
  });

  describe('getRecoveryActions()', () => {
    it('should include retry action for retryable errors', () => {
      const error = new MASAPIError('Network error', 'network_error', 0);
      const actions = errorHandler.getRecoveryActions(error);

      const retryAction = actions.find(a => a.action === 'retry');
      expect(retryAction).toBeDefined();
    });

    it('should include refresh action for auth errors', () => {
      const error = new MASAPIError('Unauthorized', 'auth_error', 401);
      const actions = errorHandler.getRecoveryActions(error);

      const refreshAction = actions.find(a => a.action === 'refresh');
      expect(refreshAction).toBeDefined();
    });

    it('should always include dismiss action', () => {
      const error = new MASError('Test error');
      const actions = errorHandler.getRecoveryActions(error);

      const dismissAction = actions.find(a => a.action === 'dismiss');
      expect(dismissAction).toBeDefined();
    });
  });

  describe('retry()', () => {
    it('should retry operation on failure', async () => {
      let attempts = 0;
      const operation = jest.fn().mockImplementation(() => {
        attempts++;
        if (attempts < 3) {
          throw new Error('Failed');
        }
        return Promise.resolve('success');
      });

      const result = await errorHandler.retry(operation, { retryDelay: 10 });

      expect(result).toBe('success');
      expect(attempts).toBe(3);
    });

    it('should respect max retries', async () => {
      const operation = jest.fn().mockRejectedValue(new Error('Always fails'));

      await expect(
        errorHandler.retry(operation, { maxRetries: 2, retryDelay: 10 })
      ).rejects.toThrow();

      expect(operation).toHaveBeenCalledTimes(3); // Initial + 2 retries
    });

    it('should not retry non-retryable errors', async () => {
      const authError = new MASAPIError('Unauthorized', 'auth_error', 401);
      const operation = jest.fn().mockRejectedValue(authError);

      await expect(
        errorHandler.retry(operation, { retryDelay: 10 })
      ).rejects.toThrow();

      expect(operation).toHaveBeenCalledTimes(1);
    });

    it('should use exponential backoff', async () => {
      jest.useFakeTimers();

      let attempts = 0;
      const operation = jest.fn().mockImplementation(() => {
        attempts++;
        if (attempts < 3) {
          return Promise.reject(new Error('Failed'));
        }
        return Promise.resolve('success');
      });

      const promise = errorHandler.retry(operation, { retryDelay: 100 });

      // Fast-forward through delays
      await jest.runAllTimersAsync();

      await promise;

      jest.useRealTimers();
    });
  });

  describe('wrap()', () => {
    it('should wrap successful operation', async () => {
      const operation = jest.fn().mockResolvedValue('success');
      const result = await errorHandler.wrap(operation);

      expect(result).toBe('success');
    });

    it('should handle operation error', async () => {
      const operation = jest.fn().mockRejectedValue(new Error('Failed'));

      await expect(errorHandler.wrap(operation, { autoRetry: false })).rejects.toThrow();
    });

    it('should auto-retry on retryable error', async () => {
      let attempts = 0;
      const operation = jest.fn().mockImplementation(() => {
        attempts++;
        if (attempts < 2) {
          throw new MASAPIError('Network error', 'network_error', 0);
        }
        return Promise.resolve('success');
      });

      const result = await errorHandler.wrap(operation, { retryDelay: 10 });

      expect(result).toBe('success');
      expect(attempts).toBe(2);
    });
  });

  describe('error history', () => {
    it('should add errors to history', () => {
      errorHandler.handle(new Error('Error 1'));
      errorHandler.handle(new Error('Error 2'));

      expect(errorHandler.errorHistory.length).toBe(2);
    });

    it('should get error history', () => {
      errorHandler.handle(new Error('Test error'));
      const history = errorHandler.getHistory();

      expect(history).toBeInstanceOf(Array);
      expect(history.length).toBe(1);
    });

    it('should clear error history', () => {
      errorHandler.handle(new Error('Test error'));
      errorHandler.clearHistory();

      expect(errorHandler.errorHistory).toEqual([]);
    });

    it('should limit history size', () => {
      for (let i = 0; i < 100; i++) {
        errorHandler.handle(new Error(`Error ${i}`));
      }

      expect(errorHandler.errorHistory.length).toBeLessThanOrEqual(errorHandler.maxHistorySize);
    });
  });

  describe('static methods', () => {
    it('should create validation error', () => {
      const errors = { field1: 'Error 1' };
      const error = ErrorHandler.createValidationError(errors);

      expect(error).toBeInstanceOf(MASValidationError);
      expect(error.errors).toEqual(errors);
    });

    it('should create API error', () => {
      const error = ErrorHandler.createAPIError('API failed', 'api_error', 500);

      expect(error).toBeInstanceOf(MASAPIError);
      expect(error.status).toBe(500);
    });

    it('should check if error is retryable', () => {
      const retryableError = new MASAPIError('Network error', 'network_error', 0);
      const nonRetryableError = new MASAPIError('Unauthorized', 'auth_error', 401);

      expect(ErrorHandler.isRetryable(retryableError)).toBe(true);
      expect(ErrorHandler.isRetryable(nonRetryableError)).toBe(false);
    });
  });
});
