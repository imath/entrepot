const { Component, render, createElement, Fragment } = wp.element;
const { __ } = wp.i18n;
const { apiFetch } = wp;
const { pick } = lodash;

class ManageBlocks extends Component {
    constructor() {
        super( ...arguments );

        this.state = {
            blocks: [],
            status: 'loading',
            message: '',
            tab: 'installed',
        };

        this.handleTabSwitch = this.handleTabSwitch.bind( this );
        this.getBlocks = this.getBlocks.bind( this );
    }

    getBlocks( tab ) {
        const path = !! tab ? '/wp/v2/entrepot-blocks?tab=' + tab : '/wp/v2/entrepot-blocks?tab=installed';

        apiFetch( { path: path } ).then( types => {
            this.setState( { blocks: types, status: 'success',  message: '' } );
        }, error => {
            this.setState( { status: 'error', message: error.message } );
        } );
    }

    componentDidMount() {
        this.getBlocks();
    }

    handleTabSwitch( tab ) {
        this.setState( { status: 'loading', tab: tab } );
        this.getBlocks( tab );
    }

    render() {
        const { blocks, status, message, tab } = this.state;
        let blockTypes, loader;

        if ( 'success' === status ) {
            blockTypes = blocks.map( ( block ) => {
                let actions = pick( block._links, [ 'activate', 'deactivate', 'install', 'delete' ] );

                return (
                    <Block
                        key={ 'block-' + block.id }
                        id={ block.id }
                        name={ block.name }
                        description={ block.description }
                        README={ block.README }
                        icon={ block.icon }
                        author={ block.author }
                        actions={ actions }
                    />
                );
            } );
        }

        if ( 'loading' === status ) {
            loader = <p>{ __( 'Chargement en cours, merci de patienter.', 'entrepot' ) }</p>;
        }

        return (
            <Fragment>
                <h2 className="screen-reader-text">{ __( 'Liste de blocs', 'entrepot' ) }</h2>
                <BlockFilters
                    current= { tab }
                    onTabSwitch={ this.handleTabSwitch }
                />
                <div className="blocks">
                    { loader }
                    { blockTypes }
                    { message && (
                        <p> { message } </p>
                    ) }
                </div>
            </Fragment>
        );
    }
}

class BlockFilters extends Component {
    constructor() {
        super( ...arguments );

        this.switchTab = this.switchTab.bind( this );
        this.isCurrentTab = this.isCurrentTab.bind( this );
    }

    switchTab( tab, event ) {
        event.preventDefault();

        this.props.onTabSwitch( tab );
    }

    isCurrentTab( tab ) {
        return tab === this.props.current ? 'current' : '';
    }

    render() {
        return (
            <div class="wp-filter">
                <ul class="filter-links">
                    <li id="installed-blocks">
                        <a href="#installed-blocks" onClick={ ( e ) => this.switchTab( 'installed', e ) } className={ this.isCurrentTab( 'installed' ) }>
                            { __( 'Installés', 'entrepot' ) }
                        </a>
                    </li>
                    <li id="available-blocks">
                        <a href="#available-blocks" onClick={ ( e ) => this.switchTab( 'available', e ) } className={ this.isCurrentTab( 'available' ) }>
                            { __( 'Disponibles', 'entrepot' ) }
                        </a>
                    </li>
                </ul>
            </div>
        );
    }
}

class Block extends Component {
    render() {
        const { actions } = this.props;
        const actionLinks = Object.values( actions ).map( ( action ) => (
            <li>
                <a
                    href={ action[0].href }
                    onClick={ ( e ) => { action[0].confirm && ! window.confirm( action[0].confirm ) ? e.preventDefault() : e } }
                    className={ action[0].classes } aria-label={ action[0].title }
                >
                    { action[0].title }
                </a>
            </li>
        ) );

        return (
            <div className="block plugin-card">
                <div className="plugin-card-top">
                    <div className="name column-name">
                        <h3>
                            <a href={ this.props.README } className="thickbox open-plugin-details-modal">
                                { this.props.name }
                                <img src={ this.props.icon } className="plugin-icon" alt=""/>
                            </a>
                        </h3>
                    </div>
                    <div class="action-links">
                        <ul class="plugin-action-buttons">
                            { actionLinks }
                        </ul>
                    </div>
                    <div className="desc column-description">
                        <p>{ this.props.description }</p>
                        <p className="authors">
                            <cite>{ this.props.author }</cite>
                        </p>
                    </div>
                </div>
            </div>
        );
    }
}

render( <ManageBlocks />, document.querySelector( '#entrepot-blocks' ) );
