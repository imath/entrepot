const { render, createElement } = wp.element;
const { __ } = wp.i18n;
const { apiFetch } = wp;

const ManageBlocks = () => {
    apiFetch( { path: '/wp/v2/entrepot-blocks' } ).then( types => {
        console.log( types );
    } );

    return <p> { __( 'Liste de blocs', 'entrepot' ) } </p>
};

render( <ManageBlocks />, document.querySelector( '#entrepot-blocks' ) );
