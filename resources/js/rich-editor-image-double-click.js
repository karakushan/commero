const { Extension } = window.FilamentRichEditor.tiptap.core
const { NodeSelection, Plugin } = window.FilamentRichEditor.tiptap.pmState

const findImageNode = (view, position) => {
    const document = view.state.doc
    let image = null

    document.nodesBetween(
        Math.max(0, position - 2),
        Math.min(document.content.size, position + 2),
        (node, nodePosition) => {
            if (node.type.name === 'image') {
                image = { node, position: nodePosition }
            }
        },
    )

    return image
}

const mountImageAction = (view, image) => {
    const livewireRoot = view.dom.closest('[wire\\:id]')
    const livewireId = livewireRoot?.getAttribute('wire:id')
    const wire = livewireId ? window.Livewire?.find(livewireId) : null
    const schemaComponent = view.dom.closest('[id]')?.id

    if (!wire || !schemaComponent) {
        return false
    }

    const editorSelection = NodeSelection.create(
        view.state.doc,
        image.position,
    ).toJSON()

    view.dispatch(
        view.state.tr.setSelection(
            NodeSelection.create(view.state.doc, image.position),
        ),
    )

    wire.mountAction(
        'insertImage',
        {
            editorSelection,
            alt: image.node.attrs.alt,
            title: image.node.attrs.title,
            width: image.node.attrs.width,
            height: image.node.attrs.height,
            id: image.node.attrs.id,
            src: image.node.attrs.src,
        },
        { schemaComponent },
    )

    return true
}

export default () => Extension.create({
    name: 'commeroImageDoubleClick',

    addProseMirrorPlugins() {
        return [
            new Plugin({
                props: {
                    handleDoubleClick: (view, position) => {
                        const image = findImageNode(view, position)

                        return image ? mountImageAction(view, image) : false
                    },
                },
            }),
        ]
    },
})
