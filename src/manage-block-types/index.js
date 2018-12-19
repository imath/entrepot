const { render, createElement } = wp.element;
const { __ } = wp.i18n;

const ManageBlocks = () => <p> { __( 'Liste de blocs', 'entrepot' ) } </p>;

render( <ManageBlocks />, document.querySelector( '#entrepot-blocks' ) );
