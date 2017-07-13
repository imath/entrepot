window.wp = window.wp || {};
window.entrepot = window.entrepot || _.extend( {}, _.pick( window.wp, 'Backbone', 'ajax', 'template' ) );

( function( entrepot, $ ) {

	if ( 'undefined' === typeof entrepotUpgraderl10n  ) {
		return;
	}

	// Init Models and Collections
	entrepot.Models      = entrepot.Models || {};
	entrepot.Collections = entrepot.Collections || {};

	// Init Views
	entrepot.Views = entrepot.Views || {};

	/**
	 * The Tasks collection
	 */
	entrepot.Collections.Tasks = Backbone.Collection.extend( {
		proceed: function( options ) {
			options         = options || {};
			options.context = this;
			options.data    = options.data || {};
			options.url     = '';
			options.beforeSend = function( xhr ) {
				xhr.setRequestHeader( 'X-Entrepot-Nonce', entrepotUpgraderl10n.entrepot_nonce );
				xhr.setRequestHeader( 'Accept', 'application/json' );
			};

			return entrepot.ajax.send( options );
		},
	} );

	/**
	 * Extend Backbone.View with .prepare() and .inject()
	 */
	entrepot.View = entrepot.Backbone.View.extend( {
		inject: function( selector ) {
			this.render();
			$( selector ).html( this.el );
			this.views.ready();
		},

		prepare: function() {
			if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
				return this.model.toJSON();
			} else {
				return {};
			}
		}
	} );

	entrepot.Views.Card = entrepot.View.extend( {
		className:  'repository-card',
		template: entrepot.template( 'repository-card' )
	} );

	entrepot.Views.Cards = entrepot.View.extend( {
		initialize: function() {
			_.each( this.collection.models, function( repository ) {
				this.displayRepository( repository );
			}, this );
		},

		displayRepository: function( repository ) {
			this.views.add( new entrepot.Views.Card( { model: repository } ) );
		}
	} );

	/**
	 * List of tasks view
	 */
	entrepot.Views.Upgrader = entrepot.View.extend( {
		tagName   : 'div',

		initialize: function() {
			this.views.add( new entrepot.View( { tagName: 'ul', id: 'entrepot-upgrader-tasks' } ) );

			this.collection.on( 'add', this.injectTask, this );
			this.collection.on( 'change:active', this.manageQueue, this );
			this.collection.on( 'change:done', this.manageQueue, this );
		},

		taskSuccess: function( response ) {
			var task, next, nextTask;

			if ( response.done && response.callback ) {
				task = this.get( response.callback );

				task.set( 'done', Number( response.done ) + Number( task.get( 'done' ) ) );

				if ( Number( task.get( 'count' ) ) === Number( task.get( 'done' ) ) ) {
					task.set( 'active', false );

					next     = Number( task.get( 'order' ) ) + 1;
					nextTask = this.findWhere( { order: next } );

					if ( _.isObject( nextTask ) ) {
						nextTask.set( 'active', true );
					}
				}
			}
		},

		taskError: function( response ) {
			if ( response.message && response.callback ) {
				if ( 'warning' === response.type ) {
					var task = this.get( response.callback );
					response.message = response.message.replace( '%d', Number( task.get( 'count' ) ) - Number( task.get( 'done' ) ) );
				}

				$( '#' + response.callback + ' .upgrade-progress' ).html( response.message ).addClass( response.type );
			}
		},

		injectTask: function( task ) {
			this.views.add( '#entrepot-upgrader-tasks', new entrepot.Views.Task( { model: task } ) );
		},

		manageQueue: function( task ) {
			if ( true === task.get( 'active' ) ) {
				this.collection.proceed( {
					data    : _.pick( task.attributes, ['id', 'count', 'number', 'done'] ),
					success : this.taskSuccess,
					error   : this.taskError
				} );
			}
		}
	} );

	/**
	 * The task view
	 */
	entrepot.Views.Task = entrepot.View.extend( {
		tagName   : 'li',
		template  : entrepot.template( 'progress-window' ),
		className : 'entrepot-upgrader-task',

		initialize: function() {
			this.model.on( 'change:done', this.taskProgress, this );
			this.model.on( 'change:active', this.addClass, this );

			if ( 0 === this.model.get( 'order' ) ) {
				this.model.set( 'active', true );
			}
		},

		addClass: function( task ) {
			if ( true === task.get( 'active' ) ) {
				$( this.$el ).addClass( 'active' );
			}
		},

		taskProgress: function( task ) {
			if ( ! _.isUndefined( task.get( 'done' ) ) && ! _.isUndefined( task.get( 'count' ) ) ) {
				var percent = ( Number( task.get( 'done' ) ) / Number( task.get( 'count' ) ) ) * 100;
				$( '#' + task.get( 'id' ) + ' .upgrade-progress .upgrade-bar' ).css( 'width', percent + '%' );
			}
		}
	} );

	/**
	 * The Upgrader!
	 */
	entrepot.Upgrader = {
		/**
		 * Populate the tasks collection
		 */
		setUpTasks: function() {
			var self = this;

			_.each( entrepotUpgraderl10n.tasks, function( task, index ) {
				if ( ! _.isObject( task ) ) {
					return;
				}

				self.tasks.add( {
					id      : task.callback,
					order   : index,
					message : task.message,
					count   : task.count,
					number  : task.number,
					done    : 0,
					active  : false
				} );
			} );
		},

		/**
		 * Launcher
		 */
		start: function() {
			/*this.tasks = new entrepot.Collections.Tasks();
			this.completed = false;

			// Create the task list view
			var task_list = new entrepot.Views.Upgrader( { collection: this.tasks } );

			task_list.inject( '#entrepot-upgrader' );

			this.setUpTasks();*/
			this.repositories = new Backbone.Collection( entrepotUpgraderl10n.repositories );

			this.cards = new entrepot.Views.Cards( {
				el:           $( '#entrepot-cards' ),
				collection:   this.repositories
			} ).render();
		}
	};

	entrepot.Upgrader.start();

} )( entrepot, jQuery );
