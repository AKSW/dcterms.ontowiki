<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2012, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Dublic Core extension plugin
 *
 * @category ontowiki
 * @package  ontowiki_extensions_dcterms
 * @author   Michael Martin
 * @author   {@link http://sebastian.tramp.name Sebastian Tramp}
 */
class DctermsPlugin extends OntoWiki_Plugin
{
    public function onAddStatement($event)
    {
        $g = $event->graphUri;
        $s = $event->statement['subject'];
        $p = $event->statement['predicate'];
        $o = $event->statement['object'];
        $this->_addAdditionalStatements($g, $s);
    }

    public function onAddMultipleStatements($event)
    {
        $g = $event->graphUri;
        foreach ($event->statements as $s => $predicatesArray) {
            $this->_addAdditionalStatements($g, $s);
        }
    }

    public function onDeleteMultipleStatements($event)
    {
        $g = $event->graphUri;
        foreach ($event->statements as $s => $predicatesArray) {
            $this->_addAdditionalStatements($g, $s);
        }
    }

    protected function _addAdditionalStatements($g, $s)
    {
        $store  = Erfurt_App::getInstance()->getStore();
        $ow = OntoWiki::getInstance();

        $model  = new Erfurt_Rdf_Model($g);
        $resource = $model->getResource($s)->getMemoryModel();
        $user = $ow->getUser();

        $isNewResource = $isUnmodifiedResource = true;
        $statements = $resource->getStatements();

var_dump($statements, count($statements));die;
        if (count($statements) > 0 ) {
                $isNewResource = false;
        }

        foreach ($statements as $s => $po) {
            foreach ($po as $p => $os) {
                if ($p == $this->_privateConfig->contributor) {
                    $isUnmodifiedResource = false;
                }
            }
        }

        $currentDate = date("Y-m-d");
        $newResource = clone $resource;
        if ($isNewResource) {
            #add creation entries
            $newResource->addRelation($s, $this->_privateConfig->creator, $user->getUri());
            $newResource->addAttribute($s, $this->_privateConfig->creationDate, $currentDate , null, "http://www.w3.org/2001/XMLSchema#date");
        } else {
            if (!$isUnmodifiedResource) {
                #delete old modification entries
                $newResource->removeSP($s, $this->_privateConfig->contributor);
                $newResource->removeSP($s, $this->_privateConfig->modified);
            }
            # add modification entries
            $newResource->addRelation($s, $this->_privateConfig->contributor, $user->getUri());
            $newResource->addAttribute($s, $this->_privateConfig->modified, $currentDate , null, "http://www.w3.org/2001/XMLSchema#date");

        }
        $model->updateWithMutualDifference($resource->getStatements(), $newResource->getStatements());
    }

    protected function _logError($msg)
    {
        if (is_array($msg)) {
            $this->_log('dcterms Plugin Error - ' . var_export($msg, true));
        } else {
            $this->_log('dcterms Plugin Error - ' . $msg);
        }
    }

    protected function _logInfo($msg)
    {
        if (is_array($msg)) {
            $this->_log('dcterms Plugin Info - ' . var_export($msg, true));
        } else {
            $this->_log('dcterms Plugin Info - ' . $msg);
        }
    }

    private function _log($msg)
    {
        $logger = OntoWiki::getInstance()->getCustomLogger('dcterms_plugin');
        $logger->debug($msg);
    }

}
