// modules are defined as an array
// [ module function, map of requires ]
//
// map of requires is short require name -> numeric require
//
// anything defined in a previous bundle is accessed via the
// orig method which is the require for previous bundles
parcelRequire = (function (modules, cache, entry, globalName) {
  // Save the require from previous bundle to this closure if any
  var previousRequire = typeof parcelRequire === 'function' && parcelRequire;
  var nodeRequire = typeof require === 'function' && require;

  function newRequire(name, jumped) {
    if (!cache[name]) {
      if (!modules[name]) {
        // if we cannot find the module within our internal map or
        // cache jump to the current global require ie. the last bundle
        // that was added to the page.
        var currentRequire = typeof parcelRequire === 'function' && parcelRequire;
        if (!jumped && currentRequire) {
          return currentRequire(name, true);
        }

        // If there are other bundles on this page the require from the
        // previous one is saved to 'previousRequire'. Repeat this as
        // many times as there are bundles until the module is found or
        // we exhaust the require chain.
        if (previousRequire) {
          return previousRequire(name, true);
        }

        // Try the node require function if it exists.
        if (nodeRequire && typeof name === 'string') {
          return nodeRequire(name);
        }

        var err = new Error('Cannot find module \'' + name + '\'');
        err.code = 'MODULE_NOT_FOUND';
        throw err;
      }

      localRequire.resolve = resolve;
      localRequire.cache = {};

      var module = cache[name] = new newRequire.Module(name);

      modules[name][0].call(module.exports, localRequire, module, module.exports, this);
    }

    return cache[name].exports;

    function localRequire(x){
      return newRequire(localRequire.resolve(x));
    }

    function resolve(x){
      return modules[name][1][x] || x;
    }
  }

  function Module(moduleName) {
    this.id = moduleName;
    this.bundle = newRequire;
    this.exports = {};
  }

  newRequire.isParcelRequire = true;
  newRequire.Module = Module;
  newRequire.modules = modules;
  newRequire.cache = cache;
  newRequire.parent = previousRequire;
  newRequire.register = function (id, exports) {
    modules[id] = [function (require, module) {
      module.exports = exports;
    }, {}];
  };

  var error;
  for (var i = 0; i < entry.length; i++) {
    try {
      newRequire(entry[i]);
    } catch (e) {
      // Save first error but execute all entries
      if (!error) {
        error = e;
      }
    }
  }

  if (entry.length) {
    // Expose entry point to Node, AMD or browser globals
    // Based on https://github.com/ForbesLindesay/umd/blob/master/template.js
    var mainExports = newRequire(entry[entry.length - 1]);

    // CommonJS
    if (typeof exports === "object" && typeof module !== "undefined") {
      module.exports = mainExports;

    // RequireJS
    } else if (typeof define === "function" && define.amd) {
     define(function () {
       return mainExports;
     });

    // <script>
    } else if (globalName) {
      this[globalName] = mainExports;
    }
  }

  // Override the current require with this new one
  parcelRequire = newRequire;

  if (error) {
    // throw error from earlier, _after updating parcelRequire_
    throw error;
  }

  return newRequire;
})({"../../node_modules/@babel/runtime/helpers/classCallCheck.js":[function(require,module,exports) {
function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError("Cannot call a class as a function");
  }
}

module.exports = _classCallCheck;
},{}],"../../node_modules/@babel/runtime/helpers/createClass.js":[function(require,module,exports) {
function _defineProperties(target, props) {
  for (var i = 0; i < props.length; i++) {
    var descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ("value" in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

module.exports = _createClass;
},{}],"../../node_modules/@babel/runtime/helpers/typeof.js":[function(require,module,exports) {
function _typeof(obj) {
  "@babel/helpers - typeof";

  if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") {
    module.exports = _typeof = function _typeof(obj) {
      return typeof obj;
    };
  } else {
    module.exports = _typeof = function _typeof(obj) {
      return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
    };
  }

  return _typeof(obj);
}

module.exports = _typeof;
},{}],"../../node_modules/@babel/runtime/helpers/assertThisInitialized.js":[function(require,module,exports) {
function _assertThisInitialized(self) {
  if (self === void 0) {
    throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
  }

  return self;
}

module.exports = _assertThisInitialized;
},{}],"../../node_modules/@babel/runtime/helpers/possibleConstructorReturn.js":[function(require,module,exports) {
var _typeof = require("../helpers/typeof");

var assertThisInitialized = require("./assertThisInitialized");

function _possibleConstructorReturn(self, call) {
  if (call && (_typeof(call) === "object" || typeof call === "function")) {
    return call;
  }

  return assertThisInitialized(self);
}

module.exports = _possibleConstructorReturn;
},{"../helpers/typeof":"../../node_modules/@babel/runtime/helpers/typeof.js","./assertThisInitialized":"../../node_modules/@babel/runtime/helpers/assertThisInitialized.js"}],"../../node_modules/@babel/runtime/helpers/getPrototypeOf.js":[function(require,module,exports) {
function _getPrototypeOf(o) {
  module.exports = _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
    return o.__proto__ || Object.getPrototypeOf(o);
  };
  return _getPrototypeOf(o);
}

module.exports = _getPrototypeOf;
},{}],"../../node_modules/@babel/runtime/helpers/setPrototypeOf.js":[function(require,module,exports) {
function _setPrototypeOf(o, p) {
  module.exports = _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
    o.__proto__ = p;
    return o;
  };

  return _setPrototypeOf(o, p);
}

module.exports = _setPrototypeOf;
},{}],"../../node_modules/@babel/runtime/helpers/inherits.js":[function(require,module,exports) {
var setPrototypeOf = require("./setPrototypeOf");

function _inherits(subClass, superClass) {
  if (typeof superClass !== "function" && superClass !== null) {
    throw new TypeError("Super expression must either be null or a function");
  }

  subClass.prototype = Object.create(superClass && superClass.prototype, {
    constructor: {
      value: subClass,
      writable: true,
      configurable: true
    }
  });
  if (superClass) setPrototypeOf(subClass, superClass);
}

module.exports = _inherits;
},{"./setPrototypeOf":"../../node_modules/@babel/runtime/helpers/setPrototypeOf.js"}],"index.js":[function(require,module,exports) {
"use strict";

var _classCallCheck2 = _interopRequireDefault(require("@babel/runtime/helpers/classCallCheck"));

var _createClass2 = _interopRequireDefault(require("@babel/runtime/helpers/createClass"));

var _possibleConstructorReturn2 = _interopRequireDefault(require("@babel/runtime/helpers/possibleConstructorReturn"));

var _getPrototypeOf2 = _interopRequireDefault(require("@babel/runtime/helpers/getPrototypeOf"));

var _assertThisInitialized2 = _interopRequireDefault(require("@babel/runtime/helpers/assertThisInitialized"));

var _inherits2 = _interopRequireDefault(require("@babel/runtime/helpers/inherits"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var _wp$element = wp.element,
    Component = _wp$element.Component,
    render = _wp$element.render,
    createElement = _wp$element.createElement,
    Fragment = _wp$element.Fragment;
var __ = wp.i18n.__;
var _wp = wp,
    apiFetch = _wp.apiFetch;
var _lodash = lodash,
    pick = _lodash.pick;

var ManageBlocks =
/*#__PURE__*/
function (_Component) {
  (0, _inherits2.default)(ManageBlocks, _Component);

  function ManageBlocks() {
    var _this;

    (0, _classCallCheck2.default)(this, ManageBlocks);
    _this = (0, _possibleConstructorReturn2.default)(this, (0, _getPrototypeOf2.default)(ManageBlocks).apply(this, arguments));
    _this.state = {
      blocks: [],
      status: 'loading',
      message: '',
      tab: 'installed'
    };
    _this.handleTabSwitch = _this.handleTabSwitch.bind((0, _assertThisInitialized2.default)(_this));
    _this.getBlocks = _this.getBlocks.bind((0, _assertThisInitialized2.default)(_this));
    return _this;
  }

  (0, _createClass2.default)(ManageBlocks, [{
    key: "getBlocks",
    value: function getBlocks(tab) {
      var _this2 = this;

      var path = !!tab ? '/wp/v2/entrepot-blocks?tab=' + tab : '/wp/v2/entrepot-blocks?tab=installed';
      apiFetch({
        path: path
      }).then(function (types) {
        _this2.setState({
          blocks: types,
          status: 'success',
          message: ''
        });
      }, function (error) {
        _this2.setState({
          status: 'error',
          message: error.message
        });
      });
    }
  }, {
    key: "componentDidMount",
    value: function componentDidMount() {
      this.getBlocks();
    }
  }, {
    key: "handleTabSwitch",
    value: function handleTabSwitch(tab) {
      this.setState({
        status: 'loading',
        tab: tab
      });
      this.getBlocks(tab);
    }
  }, {
    key: "render",
    value: function render() {
      var _this$state = this.state,
          blocks = _this$state.blocks,
          status = _this$state.status,
          message = _this$state.message,
          tab = _this$state.tab;
      var blockTypes, loader;

      if ('success' === status) {
        blockTypes = blocks.map(function (block) {
          var actions = pick(block._links, ['activate', 'deactivate', 'install', 'update', 'changelog', 'delete']);
          return createElement(Block, {
            key: 'block-' + block.id,
            id: block.id,
            name: block.name,
            description: block.description,
            info: block._links.block_information ? block._links.block_information[0].href : null,
            icon: block.icon,
            author: block.author,
            actions: actions,
            dependencies: block.dependencies,
            requirements: block.requirements
          });
        });
      }

      if ('loading' === status) {
        loader = createElement("div", {
          className: "entrepot-blocks-loader"
        }, createElement("span", {
          className: "spinner is-active"
        }), createElement("p", null, __('Chargement en cours, merci de patienter.', 'entrepot')));
      }

      return createElement(Fragment, null, createElement("h2", {
        className: "screen-reader-text"
      }, __('Liste de blocs', 'entrepot')), createElement(BlockFilters, {
        current: tab,
        onTabSwitch: this.handleTabSwitch
      }), createElement("div", {
        className: "blocks"
      }, loader, blockTypes, message && 'loading' !== status && createElement("div", {
        className: "no-plugin-results"
      }, " ", message, " ")));
    }
  }]);
  return ManageBlocks;
}(Component);

var BlockFilters =
/*#__PURE__*/
function (_Component2) {
  (0, _inherits2.default)(BlockFilters, _Component2);

  function BlockFilters() {
    var _this3;

    (0, _classCallCheck2.default)(this, BlockFilters);
    _this3 = (0, _possibleConstructorReturn2.default)(this, (0, _getPrototypeOf2.default)(BlockFilters).apply(this, arguments));
    _this3.switchTab = _this3.switchTab.bind((0, _assertThisInitialized2.default)(_this3));
    _this3.isCurrentTab = _this3.isCurrentTab.bind((0, _assertThisInitialized2.default)(_this3));
    return _this3;
  }

  (0, _createClass2.default)(BlockFilters, [{
    key: "switchTab",
    value: function switchTab(tab, event) {
      event.preventDefault();
      this.props.onTabSwitch(tab);
    }
  }, {
    key: "isCurrentTab",
    value: function isCurrentTab(tab) {
      return tab === this.props.current ? 'current' : '';
    }
  }, {
    key: "render",
    value: function render() {
      var _this4 = this;

      return createElement("div", {
        className: "wp-filter"
      }, createElement("ul", {
        className: "filter-links"
      }, createElement("li", {
        id: "installed-blocks"
      }, createElement("a", {
        href: "#installed-blocks",
        onClick: function onClick(e) {
          return _this4.switchTab('installed', e);
        },
        className: this.isCurrentTab('installed')
      }, __('Installés', 'entrepot'))), createElement("li", {
        id: "available-blocks"
      }, createElement("a", {
        href: "#available-blocks",
        onClick: function onClick(e) {
          return _this4.switchTab('available', e);
        },
        className: this.isCurrentTab('available')
      }, __('Disponibles', 'entrepot')))));
    }
  }]);
  return BlockFilters;
}(Component);

var Block =
/*#__PURE__*/
function (_Component3) {
  (0, _inherits2.default)(Block, _Component3);

  function Block() {
    (0, _classCallCheck2.default)(this, Block);
    return (0, _possibleConstructorReturn2.default)(this, (0, _getPrototypeOf2.default)(Block).apply(this, arguments));
  }

  (0, _createClass2.default)(Block, [{
    key: "render",
    value: function render() {
      var _this$props = this.props,
          actions = _this$props.actions,
          dependencies = _this$props.dependencies,
          requirements = _this$props.requirements;
      var hasCardBottom = dependencies || requirements.warnings && 1 <= requirements.warnings.length;
      var actionLinks = Object.values(actions).map(function (action, key) {
        return createElement("li", {
          key: key
        }, createElement("a", {
          href: action[0].href,
          onClick: function onClick(e) {
            action[0].confirm && !window.confirm(action[0].confirm) ? e.preventDefault() : e;
          },
          className: action[0].classes,
          "aria-label": action[0].title
        }, action[0].title));
      });
      var dependenciesOuptut = null;

      if (dependencies) {
        dependenciesOuptut = createElement(Fragment, null, createElement("div", {
          className: "column-downloaded"
        }, __('Dépendance(s) insatisfaite(s):', 'entrepot')), createElement("div", {
          className: "column-compatibility"
        }, createElement("ul", null, dependencies.map(function (dependency, key) {
          return createElement("li", {
            key: key
          }, createElement("strong", null, dependency));
        }))));
      }

      var requirementsOuptut = null;

      if (requirements.warnings && 1 <= requirements.warnings.length) {
        requirementsOuptut = createElement("div", {
          className: "column-requirements"
        }, createElement("ul", null, requirements.warnings.map(function (warning, key) {
          return createElement("li", {
            key: key
          }, createElement("span", {
            className: "attention"
          }, warning));
        })));
      }

      return createElement("div", {
        key: this.props.id,
        className: "block plugin-card"
      }, createElement("div", {
        className: "plugin-card-top"
      }, createElement("div", {
        className: "name column-name"
      }, createElement("h3", null, createElement("a", {
        href: this.props.info,
        className: "thickbox open-plugin-details-modal"
      }, this.props.name, createElement("img", {
        src: this.props.icon,
        className: "plugin-icon",
        alt: ""
      })))), createElement("div", {
        className: "action-links"
      }, createElement("ul", {
        className: "plugin-action-buttons"
      }, actionLinks)), createElement("div", {
        className: "desc column-description"
      }, createElement("p", null, this.props.description), createElement("p", {
        className: "authors"
      }, createElement("cite", null, this.props.author)))), true === hasCardBottom && createElement("div", {
        className: "plugin-card-bottom"
      }, dependenciesOuptut, requirementsOuptut));
    }
  }]);
  return Block;
}(Component);

render(createElement(ManageBlocks, null), document.querySelector('#entrepot-blocks'));
},{"@babel/runtime/helpers/classCallCheck":"../../node_modules/@babel/runtime/helpers/classCallCheck.js","@babel/runtime/helpers/createClass":"../../node_modules/@babel/runtime/helpers/createClass.js","@babel/runtime/helpers/possibleConstructorReturn":"../../node_modules/@babel/runtime/helpers/possibleConstructorReturn.js","@babel/runtime/helpers/getPrototypeOf":"../../node_modules/@babel/runtime/helpers/getPrototypeOf.js","@babel/runtime/helpers/assertThisInitialized":"../../node_modules/@babel/runtime/helpers/assertThisInitialized.js","@babel/runtime/helpers/inherits":"../../node_modules/@babel/runtime/helpers/inherits.js"}],"../../node_modules/parcel-bundler/src/builtins/hmr-runtime.js":[function(require,module,exports) {
var global = arguments[3];
var OVERLAY_ID = '__parcel__error__overlay__';
var OldModule = module.bundle.Module;

function Module(moduleName) {
  OldModule.call(this, moduleName);
  this.hot = {
    data: module.bundle.hotData,
    _acceptCallbacks: [],
    _disposeCallbacks: [],
    accept: function (fn) {
      this._acceptCallbacks.push(fn || function () {});
    },
    dispose: function (fn) {
      this._disposeCallbacks.push(fn);
    }
  };
  module.bundle.hotData = null;
}

module.bundle.Module = Module;
var checkedAssets, assetsToAccept;
var parent = module.bundle.parent;

if ((!parent || !parent.isParcelRequire) && typeof WebSocket !== 'undefined') {
  var hostname = "" || location.hostname;
  var protocol = location.protocol === 'https:' ? 'wss' : 'ws';
  var ws = new WebSocket(protocol + '://' + hostname + ':' + "54213" + '/');

  ws.onmessage = function (event) {
    checkedAssets = {};
    assetsToAccept = [];
    var data = JSON.parse(event.data);

    if (data.type === 'update') {
      var handled = false;
      data.assets.forEach(function (asset) {
        if (!asset.isNew) {
          var didAccept = hmrAcceptCheck(global.parcelRequire, asset.id);

          if (didAccept) {
            handled = true;
          }
        }
      }); // Enable HMR for CSS by default.

      handled = handled || data.assets.every(function (asset) {
        return asset.type === 'css' && asset.generated.js;
      });

      if (handled) {
        console.clear();
        data.assets.forEach(function (asset) {
          hmrApply(global.parcelRequire, asset);
        });
        assetsToAccept.forEach(function (v) {
          hmrAcceptRun(v[0], v[1]);
        });
      } else if (location.reload) {
        // `location` global exists in a web worker context but lacks `.reload()` function.
        location.reload();
      }
    }

    if (data.type === 'reload') {
      ws.close();

      ws.onclose = function () {
        location.reload();
      };
    }

    if (data.type === 'error-resolved') {
      console.log('[parcel] ✨ Error resolved');
      removeErrorOverlay();
    }

    if (data.type === 'error') {
      console.error('[parcel] 🚨  ' + data.error.message + '\n' + data.error.stack);
      removeErrorOverlay();
      var overlay = createErrorOverlay(data);
      document.body.appendChild(overlay);
    }
  };
}

function removeErrorOverlay() {
  var overlay = document.getElementById(OVERLAY_ID);

  if (overlay) {
    overlay.remove();
  }
}

function createErrorOverlay(data) {
  var overlay = document.createElement('div');
  overlay.id = OVERLAY_ID; // html encode message and stack trace

  var message = document.createElement('div');
  var stackTrace = document.createElement('pre');
  message.innerText = data.error.message;
  stackTrace.innerText = data.error.stack;
  overlay.innerHTML = '<div style="background: black; font-size: 16px; color: white; position: fixed; height: 100%; width: 100%; top: 0px; left: 0px; padding: 30px; opacity: 0.85; font-family: Menlo, Consolas, monospace; z-index: 9999;">' + '<span style="background: red; padding: 2px 4px; border-radius: 2px;">ERROR</span>' + '<span style="top: 2px; margin-left: 5px; position: relative;">🚨</span>' + '<div style="font-size: 18px; font-weight: bold; margin-top: 20px;">' + message.innerHTML + '</div>' + '<pre>' + stackTrace.innerHTML + '</pre>' + '</div>';
  return overlay;
}

function getParents(bundle, id) {
  var modules = bundle.modules;

  if (!modules) {
    return [];
  }

  var parents = [];
  var k, d, dep;

  for (k in modules) {
    for (d in modules[k][1]) {
      dep = modules[k][1][d];

      if (dep === id || Array.isArray(dep) && dep[dep.length - 1] === id) {
        parents.push(k);
      }
    }
  }

  if (bundle.parent) {
    parents = parents.concat(getParents(bundle.parent, id));
  }

  return parents;
}

function hmrApply(bundle, asset) {
  var modules = bundle.modules;

  if (!modules) {
    return;
  }

  if (modules[asset.id] || !bundle.parent) {
    var fn = new Function('require', 'module', 'exports', asset.generated.js);
    asset.isNew = !modules[asset.id];
    modules[asset.id] = [fn, asset.deps];
  } else if (bundle.parent) {
    hmrApply(bundle.parent, asset);
  }
}

function hmrAcceptCheck(bundle, id) {
  var modules = bundle.modules;

  if (!modules) {
    return;
  }

  if (!modules[id] && bundle.parent) {
    return hmrAcceptCheck(bundle.parent, id);
  }

  if (checkedAssets[id]) {
    return;
  }

  checkedAssets[id] = true;
  var cached = bundle.cache[id];
  assetsToAccept.push([bundle, id]);

  if (cached && cached.hot && cached.hot._acceptCallbacks.length) {
    return true;
  }

  return getParents(global.parcelRequire, id).some(function (id) {
    return hmrAcceptCheck(global.parcelRequire, id);
  });
}

function hmrAcceptRun(bundle, id) {
  var cached = bundle.cache[id];
  bundle.hotData = {};

  if (cached) {
    cached.hot.data = bundle.hotData;
  }

  if (cached && cached.hot && cached.hot._disposeCallbacks.length) {
    cached.hot._disposeCallbacks.forEach(function (cb) {
      cb(bundle.hotData);
    });
  }

  delete bundle.cache[id];
  bundle(id);
  cached = bundle.cache[id];

  if (cached && cached.hot && cached.hot._acceptCallbacks.length) {
    cached.hot._acceptCallbacks.forEach(function (cb) {
      cb();
    });

    return true;
  }
}
},{}]},{},["../../node_modules/parcel-bundler/src/builtins/hmr-runtime.js","index.js"], null)