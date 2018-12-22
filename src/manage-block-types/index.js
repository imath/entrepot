const { Component, render, createElement, Fragment } = wp.element;
const { __ } = wp.i18n;
const { apiFetch } = wp;
const { head } = lodash;

class ManageBlocks extends Component {
    constructor() {
        super( ...arguments );

        this.state = { blocks: [] };
    }

    componentDidMount() {
        apiFetch( { path: '/wp/v2/entrepot-blocks' } ).then( types => {
            this.setState( { blocks: types } );
        } );
    }

    render() {
        const blocks = this.state.blocks.map( ( block ) => (
            <Block
                key={ 'block-' + block.id }
                id={ block.id }
                name={ block.name }
                description={ block.description }
                README={ block.README }
                icon={ block.icon }
                author={ block.author }
                action={ head( block._links.action ) }
            />
        ) );

        return (
            <Fragment>
                <h2 className="screen-reader-text">{ __( 'Liste de blocs', 'entrepot' ) }</h2>
                <div className="blocks">
                    { blocks }
                </div>
            </Fragment>
        );
    }
}

class Block extends Component {
    render() {
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
                            <li>
                                <a href={ this.props.action.href } class="button button-primary activate-now" aria-label={ this.props.action.title }>{ this.props.action.title }</a>
                            </li>
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
