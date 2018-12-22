const { Component, render, createElement, Fragment } = wp.element;
const { __ } = wp.i18n;
const { apiFetch } = wp;

class ManageBlocks extends Component {
    constructor() {
        super( ...arguments );

        this.state = { blocks: [] };
    }

    componentDidMount() {
        var that = this;

        apiFetch( { path: '/wp/v2/entrepot-blocks' } ).then( types => {
            this.setState( { blocks: types } );
        } );
    }

    render() {
        const blocks = this.state.blocks.map( ( block ) => (
            <Block
                key={'block-' + block.id}
                id={block.id}
                name={block.name}
                description={block.description}
                README={block.README}
                icon={block.icon}
                author={block.author}
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
                            <a href={this.props.README} className="thickbox open-plugin-details-modal">
                                {this.props.name}
                                <img src={this.props.icon} className="plugin-icon" alt=""/>
                            </a>
                        </h3>
                    </div>
                    <div className="desc column-description">
                        <p>{this.props.description}</p>
                        <p className="authors">
                            <cite>{this.props.author}</cite>
                        </p>
                    </div>
                </div>
            </div>
        );
    }
}

render( <ManageBlocks />, document.querySelector( '#entrepot-blocks' ) );
