import {
    DOCUMENT_ENTITY,
    NODE_ENTITY,
    JOIN_ENTITY
} from '../types/entityTypes'

import DocumentPreviewListItem from '../components/DocumentPreviewListItem.vue'
import NodePreviewItem from '../components/NodePreviewItem.vue'
import JoinPreviewItem from '../components/JoinPreviewItem.vue'

export default class EntityAwareFactory {
    static getState (entity) {
        const result = {
            trans: {
                moreItems: ''
            }
        }

        switch (entity) {
            case DOCUMENT_ENTITY:
                result.currentListingView = DocumentPreviewListItem
                result.filterExplorerIcon = 'uk-icon-rz-folder-tree-mini'
                result.trans.moreItems = 'moreDocuments'
                result.isFilterEnable = true
                break;
            case NODE_ENTITY:
                result.currentListingView = NodePreviewItem
                result.filterExplorerIcon = 'uk-icon-tags'
                result.trans.moreItems = 'moreNodes'
                result.isFilterEnable = true
                break;
            case JOIN_ENTITY:
                result.currentListingView = JoinPreviewItem
                result.trans.moreItems = 'moreEntities'
                result.isFilterEnable = false
                break;
        }

        return result
    }

    static getListingView (entity) {
        switch (entity) {
            case DOCUMENT_ENTITY:
                return DocumentPreviewListItem
            case NODE_ENTITY:
                return NodePreviewItem
            case JOIN_ENTITY:
                return JoinPreviewItem
        }
    }
}
