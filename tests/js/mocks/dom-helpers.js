/**
 * DOM helper utilities for testing
 */

/**
 * Create a mock DOM element
 */
export function createMockElement(tagName, attributes = {}) {
  const element = {
    tagName: tagName.toUpperCase(),
    id: attributes.id || '',
    className: attributes.class || '',
    innerHTML: '',
    textContent: '',
    value: attributes.value || '',
    checked: attributes.checked || false,
    disabled: attributes.disabled || false,
    style: {},
    dataset: {},
    attributes: new Map(),
    children: [],
    parentNode: null,
    classList: {
      add: jest.fn(),
      remove: jest.fn(),
      toggle: jest.fn(),
      contains: jest.fn(() => false)
    },
    setAttribute: jest.fn((name, value) => {
      element.attributes.set(name, value);
    }),
    getAttribute: jest.fn((name) => {
      return element.attributes.get(name);
    }),
    removeAttribute: jest.fn((name) => {
      element.attributes.delete(name);
    }),
    appendChild: jest.fn((child) => {
      element.children.push(child);
      child.parentNode = element;
    }),
    removeChild: jest.fn((child) => {
      const index = element.children.indexOf(child);
      if (index > -1) {
        element.children.splice(index, 1);
        child.parentNode = null;
      }
    }),
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    dispatchEvent: jest.fn(),
    querySelector: jest.fn(),
    querySelectorAll: jest.fn(() => []),
    focus: jest.fn(),
    blur: jest.fn(),
    click: jest.fn(),
    submit: jest.fn()
  };

  // Apply attributes
  Object.keys(attributes).forEach(key => {
    if (key !== 'class' && key !== 'id') {
      element.setAttribute(key, attributes[key]);
    }
  });

  return element;
}

/**
 * Create a mock form element
 */
export function createMockForm(fields = {}) {
  const form = createMockElement('form');
  const elements = [];

  Object.keys(fields).forEach(name => {
    const field = createMockElement('input', {
      name,
      value: fields[name],
      type: 'text'
    });
    elements.push(field);
    form.appendChild(field);
  });

  form.elements = elements;
  form.querySelector = jest.fn((selector) => {
    if (selector.startsWith('[name=')) {
      const name = selector.match(/\[name="?([^"\]]+)"?\]/)[1];
      return elements.find(el => el.getAttribute('name') === name);
    }
    return null;
  });

  return form;
}

/**
 * Create a mock event
 */
export function createMockEvent(type, properties = {}) {
  return {
    type,
    target: null,
    currentTarget: null,
    preventDefault: jest.fn(),
    stopPropagation: jest.fn(),
    stopImmediatePropagation: jest.fn(),
    ...properties
  };
}

/**
 * Create a mock jQuery object
 */
export function createMockJQuery(elements = []) {
  const $mock = {
    length: elements.length,
    0: elements[0],
    get: jest.fn((index) => elements[index]),
    each: jest.fn((callback) => {
      elements.forEach((el, i) => callback.call(el, i, el));
      return $mock;
    }),
    find: jest.fn(() => createMockJQuery([])),
    filter: jest.fn(() => createMockJQuery([])),
    addClass: jest.fn(() => $mock),
    removeClass: jest.fn(() => $mock),
    toggleClass: jest.fn(() => $mock),
    hasClass: jest.fn(() => false),
    attr: jest.fn((name, value) => {
      if (value === undefined) {
        return elements[0]?.getAttribute(name);
      }
      elements.forEach(el => el.setAttribute(name, value));
      return $mock;
    }),
    prop: jest.fn((name, value) => {
      if (value === undefined) {
        return elements[0]?.[name];
      }
      elements.forEach(el => { el[name] = value; });
      return $mock;
    }),
    val: jest.fn((value) => {
      if (value === undefined) {
        return elements[0]?.value;
      }
      elements.forEach(el => { el.value = value; });
      return $mock;
    }),
    text: jest.fn((text) => {
      if (text === undefined) {
        return elements[0]?.textContent;
      }
      elements.forEach(el => { el.textContent = text; });
      return $mock;
    }),
    html: jest.fn((html) => {
      if (html === undefined) {
        return elements[0]?.innerHTML;
      }
      elements.forEach(el => { el.innerHTML = html; });
      return $mock;
    }),
    css: jest.fn(() => $mock),
    show: jest.fn(() => $mock),
    hide: jest.fn(() => $mock),
    fadeIn: jest.fn(() => $mock),
    fadeOut: jest.fn(() => $mock),
    slideUp: jest.fn(() => $mock),
    slideDown: jest.fn(() => $mock),
    animate: jest.fn(() => $mock),
    on: jest.fn(() => $mock),
    off: jest.fn(() => $mock),
    one: jest.fn(() => $mock),
    trigger: jest.fn(() => $mock),
    click: jest.fn(() => $mock),
    change: jest.fn(() => $mock),
    submit: jest.fn(() => $mock),
    serialize: jest.fn(() => ''),
    serializeArray: jest.fn(() => [])
  };

  return $mock;
}

/**
 * Simulate user input
 */
export function simulateInput(element, value) {
  element.value = value;
  const event = createMockEvent('input', { target: element });
  element.dispatchEvent(event);
}

/**
 * Simulate form submission
 */
export function simulateSubmit(form) {
  const event = createMockEvent('submit', { target: form });
  form.dispatchEvent(event);
}

/**
 * Simulate click
 */
export function simulateClick(element) {
  const event = createMockEvent('click', { target: element });
  element.dispatchEvent(event);
}
