window.wp       = window.wp || {};
window.entrepot = _.extend( window.entrepot || {}, _.pick( window.wp, 'Backbone', 'ajax', 'template' ) );

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
	 * The Tasks collection.
	 *
	 * @type {Backbone.Collection}
	 */
	entrepot.Collections.Tasks = Backbone.Collection.extend( {
		/**
		 * Proceeds upgrade tasks.
		 *
		 * @param  {Object} options The request's options.
		 * @return {Object}         The JSON reply.
		 */
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
	 * Extends Backbone.View with .prepare()
	 *
	 * @type {Backbone.View}
	 */
	entrepot.View = entrepot.Backbone.View.extend( {
		prepare: function() {
			if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
				return this.model.toJSON();
			} else {
				return {};
			}
		}
	} );

	/**
	 * The single view for a repository to upgrade.
	 *
	 * @type {Backbone.View}
	 */
	entrepot.Views.Card = entrepot.View.extend( {
		className:  'repository-card',
		template: entrepot.template( 'repository-card' ),

		/**
		 * Initializes the listeners.
		 *
		 * @return {void}
		 */
		initialize: function() {
			if ( this.model.get( 'did_upgrade' ) ) {
				return;
			}

			this.listenToOnce( this.model, 'change:do_upgrade', this.displayTasks );
			this.listenToOnce( this.model, 'change:did_upgrade', this.emptyTasks );
		},

		/**
		 * Populates the tasks collection.
		 *
		 * @return {void}
		 */
		setUpTasks: function() {
			var self = this;

			if ( _.isUndefined( entrepotUpgraderl10n.tasks[ this.model.get( 'slug' ) ] ) ) {
				return;
			}

			_.each( entrepotUpgraderl10n.tasks[ this.model.get( 'slug' ) ], function( task, index ) {
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
		 * Adds a new view for each upgrade tasks.
		 *
		 * @return {void}
		 */
		displayTasks: function() {
			this.tasks = new entrepot.Collections.Tasks();

			this.views.add( '.repository-tasks', new entrepot.Views.Upgrader( { collection: this.tasks, repository: this.model } ) );

			this.setUpTasks();
		},

		/**
		 * Removes task views on a successfull upgrade.
		 *
		 * @param  {Backbone.Model} model The upgraded repository.
		 * @return {void}
		 */
		emptyTasks: function( model ) {
			var self = this;

			if ( false === model.get( 'did_upgrade' ) ) {
				return;
			}

			window.setTimeout( function() {
				_.first( self.views._views['.repository-tasks'] ).remove();
			}, 1000 );
		}
	} );

	/**
	 * The view to list the repositories needing to be upgraded.
	 *
	 * @type {Backbone.View}
	 */
	entrepot.Views.Cards = entrepot.View.extend( {
		events: {
			'click .repository-do-upgrade' : 'initUpgrade'
		},

		/**
		 * Intializes the listener and add a view for each repository to upgrade.
		 *
		 * @return {void}
		 */
		initialize: function() {
			_.each( this.collection.models, function( repository ) {
				this.displayRepository( repository );
			}, this );

			this.listenTo( this.collection, 'change:did_upgrade', this.resetButtons );
		},

		/**
		 * Adds a new view for the repository's upgrades.
		 *
		 * @param  {Backbone.Model} repository The repository to upgrade.
		 * @return {void}
		 */
		displayRepository: function( repository ) {
			this.views.add( new entrepot.Views.Card( { model: repository } ) );
		},

		/**
		 * Inits the repository upgrade once the user asked for it.
		 *
		 * @param  {Object} event The button click.
		 * @return {void}
		 */
		initUpgrade: function( event ) {
			event.preventDefault();

			// Asks once the user for a confirmation
			if ( ! entrepot.Upgrader.isWarned ) {
				var carryOn = confirm( entrepotUpgraderl10n.confirm );
				entrepot.Upgrader.isWarned = true;

				if ( ! carryOn ) {
					return;
				}
			}

			var currentBtn = $( event.currentTarget ), slug = currentBtn.data( 'slug' );

			if ( currentBtn.hasClass( 'disabled' ) || ! slug || _.isUndefined( this.views._views[''] ) ) {
				return;
			}

			_.each( this.views._views[''], function( view ) {
				view.$el.find( 'button.repository-do-upgrade' ).addClass( 'disabled' );

				if ( slug === view.model.get( 'slug' ) ) {
					view.model.set( 'do_upgrade', true );
				}
			} );
		},

		/**
		 * Resets all subviews action button.
		 *
		 * @param  {Backbone.Model} model The repository being upgraded.
		 * @return {void}
		 */
		resetButtons: function( model ) {
			_.each( this.views._views[''], function( view ) {
				var btn        = view.$el.find( 'button.repository-do-upgrade' ),
				    didUpgrade = view.model.get( 'did_upgrade' );

				if ( false !== didUpgrade ) {
					btn.removeClass( 'disabled' );
				}

				if ( model.get( 'slug' ) === view.model.get( 'slug' ) && didUpgrade ) {
					view.$el.find( '.description' ).remove();
					btn.remove();

					view.$el.find( '.repository-info' ).append(
						$( '<div></div>' ).addClass( 'repository-upgraded' )
						                  .html( '<span class="dashicons dashicons-yes"></span>' + entrepotUpgraderl10n.upgraded )
					);
				}
			} );
		}
	} );

	/**
	 * View to list all upgrading tasks.
	 *
	 * @type {Backbone.View}
	 */
	entrepot.Views.Upgrader = entrepot.View.extend( {
		tagName   : 'div',

		/**
		 * Adds a container view for tasks and listens to the collection's changes.
		 *
		 * @return {void}
		 */
		initialize: function() {
			this.containerId = this.options.repository.get( 'slug' ) + '-upgrader-tasks';
			this.views.add( new entrepot.View( { tagName: 'ul', id: this.containerId } ) );

			this.collection.on( 'add', this.injectTask, this );
			this.collection.on( 'change:active', this.manageQueue, this );
			this.collection.on( 'change:done', this.manageQueue, this );
		},

		/**
		 * Success callback: moves to next task and ends the process when there are no more tasks.
		 *
		 * @param  {Object} response The JSON reply.
		 * @return {void}
		 */
		taskSuccess: function( response ) {
			var task, next, nextTask;

			if ( response.done && response.callback ) {
				task = this.tasks.get( response.callback );

				task.set( 'done', Number( response.done ) + Number( task.get( 'done' ) ) );

				if ( Number( task.get( 'count' ) ) === Number( task.get( 'done' ) ) ) {
					task.set( 'active', false );

					next     = Number( task.get( 'order' ) ) + 1;
					nextTask = this.tasks.findWhere( { order: next } );

					if ( _.isObject( nextTask ) ) {
						nextTask.set( 'active', true );
					} else {
						this.repository.set( 'did_upgrade', true );
					}
				}
			}
		},

		/**
		 * Error callback: Interrupt the Upgrade process and inform about the error.
		 *
		 * @param  {Object} response The JSON reply.
		 * @return {void}
		 */
		taskError: function( response ) {
			if ( response.message ) {
				var element;

				if ( response.callback ) {
					element = '#' + response.callback + ' .upgrade-progress';
				} else {
					element = $( '#' + this.repository.get( 'slug' ) + '-upgrader-tasks' ).prepend(
					                                                                      	$( '<li></li>' ).addClass( 'entrepot-upgrader-task' )
					                                                                                        .html( $( '<div></div>' ).addClass( 'upgrade-progress' ) )
					                                                                      )
					                                                                      .children()
					                                                                      .first()
					                                                                      .find( '.upgrade-progress' );
				}

				$( element ).html( response.message ).addClass( response.type );
			}

			this.repository.set( 'did_upgrade', false );
		},

		/**
		 * Adds a new task view to this container's views.
		 *
		 * @param  {Backbone.Model} task The upgrade task.
		 * @return {void}
		 */
		injectTask: function( task ) {
			this.views.add( '#' + this.containerId, new entrepot.Views.Task( { model: task } ) );
		},

		/**
		 * Upgrades the active task.
		 *
		 * @param  {Backbone.Model} task The upgrade task.
		 * @return {void}
		 */
		manageQueue: function( task ) {
			var options = {
				tasks:      this.collection,
				repository: this.options.repository
			};

			if ( true === task.get( 'active' ) ) {
				this.collection.proceed( {
					data    : _.pick( task.attributes, ['id', 'count', 'number', 'done'] ),
					success : _.bind( this.taskSuccess, options ),
					error   : _.bind( this.taskError, options )
				} );
			}
		}
	} );

	/**
	 * The upgrade task single view.
	 *
	 * @type {Backbone.View}
	 */
	entrepot.Views.Task = entrepot.View.extend( {
		tagName   : 'li',
		template  : entrepot.template( 'progress-window' ),
		className : 'entrepot-upgrader-task',

		/**
		 * Intializes listeners.
		 *
		 * @return {void}
		 */
		initialize: function() {
			this.model.on( 'change:done', this.taskProgress, this );
			this.model.on( 'change:active', this.addClass, this );

			if ( 0 === this.model.get( 'order' ) ) {
				this.model.set( 'active', true );
			}
		},

		/**
		 * Informs about the active task.
		 *
		 * @param  {Backbone.Model} task The upgrade task being processed.
		 * @return {void}
		 */
		addClass: function( task ) {
			if ( true === task.get( 'active' ) ) {
				$( this.$el ).addClass( 'active' );
			}
		},

		/**
		 * Informs about the progress of a task.
		 *
		 * @param  {Backbone.Model} task The upgrade task being processed.
		 * @return {void}
		 */
		taskProgress: function( task ) {
			if ( ! _.isUndefined( task.get( 'done' ) ) && ! _.isUndefined( task.get( 'count' ) ) ) {
				var percent = ( Number( task.get( 'done' ) ) / Number( task.get( 'count' ) ) ) * 100;
				$( '#' + task.get( 'id' ) + ' .upgrade-progress .upgrade-bar' ).css( 'width', percent + '%' );
			}
		}
	} );

	/**
	 * The Upgrader UI.
	 *
	 * @type {Object}
	 */
	entrepot.Upgrader = {
		/**
		 * Displays the Upgrader UI.
		 *
		 * @return {void}
		 */
		start: function() {
			this.repositories = new Backbone.Collection( entrepotUpgraderl10n.repositories );
			this.isWarned     = false;

			this.cards = new entrepot.Views.Cards( {
				el:           $( '#entrepot-cards' ),
				collection:   this.repositories
			} ).render();
		}
	};

	entrepot.Upgrader.start();

} )( entrepot, jQuery );
