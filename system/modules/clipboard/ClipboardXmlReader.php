<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  MEN AT WORK 2012
 * @package    clipboard
 * @license    GNU/GPL 2
 * @filesource
 */

/**
 * Class ClipboardXmlReader
 */
class ClipboardXmlReader extends Backend
{

    /**
     * Current object instance (Singleton)
     * @var ClipboardXmlReader
     */
    protected static $_objInstance = NULL;

    /**
     * Contains some helper functions
     * 
     * @var ClipboardHelper
     */
    protected $_objHelper;

    /**
     * Contains specific database request
     * 
     * @var ClipboardDatabase
     */
    protected $_objDatabase;
    
    /**
     * Encryption key from file
     * 
     * @var type 
     */
    protected $_strEncryptionKey;

    /**
     * Prevent constructing the object (Singleton)
     */
    protected function __construct()
    {
        parent::__construct();
        $this->_objHelper = ClipboardHelper::getInstance();
        $this->_objDatabase = ClipboardDatabase::getInstance();
    }

    /**
     * Prevent cloning of the object (Singleton)
     */
    final private function __clone(){}

    /**
     * Get instanz of the object (Singelton) 
     *
     * @return ClipboardXmlReader 
     */
    public static function getInstance()
    {
        if (self::$_objInstance == NULL)
        {
            self::$_objInstance = new ClipboardXmlReader();
        }
        return self::$_objInstance;
    }

    /**
     * Read xml file and create elements
     * 
     * @param ClipboardXmlElement $objFile
     * @param string $strPastePos
     * @param integer $intElemId 
     */
    public function readXml($objFile, $strPastePos, $intElemId)
    {
        $objXml = new XMLReader();
        $objXml->open($objFile->getFilePath('full'));
        while ($objXml->read())
        {
            switch ($objXml->nodeType)
            {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'encryptionKey':
                            $objXml->read();
                            $this->_strEncryptionKey = $objXml->value;
                            break;

                        case 'page':
                            $this->createPage($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId);
                            break;

                        case 'article':
                            $this->createArticle($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId);
                            break;

                        case 'content':
                            $this->createContent($objXml, $objXml->getAttribute("table"), $strPastePos, $intElemId);
                            break;

                        default:
                            break;
                    }
                    break;
                default:
                    break;
            }
        }
        $objXml->close();
    }

    /**
     * Create page elements
     * 
     * @param XMLReader $objXml
     * @param string $strTable
     * @param string $strPastePos
     * @param integer $intElemId
     * @param bool $boolIsChild
     */
    public function createPage(&$objXml, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE)
    {
        $intLastInsertId = 0;

        if ($boolIsChild == TRUE)
        {
            $intId = $intElemId;
        }
        else
        {
            if ($strPastePos == 'pasteAfter')
            {
                $objElem = $this->_objDatabase->getPageObject($intElemId);
                $intId = $objElem->pid;
            }
        }

        while ($objXml->read())
        {
            switch ($objXml->nodeType)
            {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'article':
                            $this->createArticle($objXml, $objXml->getAttribute("table"), $strPastePos, $intLastInsertId, TRUE);
                            break;

                        case 'row':
                            $objDb = $this->_objDatabase->insertInto($strTable, $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild));
                            $intLastInsertId = $objDb->insertId;
                            break;

                        case 'subpage':
                            $this->createPage($objXml, $objXml->getAttribute("table"), $strPastePos, $intLastInsertId, TRUE);
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'page':
                            return;
                            break;
                    }
                    break;
            }
        }
    }

    /**
     * Create article elements
     * 
     * @param XMLReader $objXml
     * @param string $strTable
     * @param string $strPastePos
     * @param integer $intElemId
     * @param bool $boolIsChild
     */
    public function createArticle(&$objXml, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE)
    {
        $intLastInsertId = 0;

        if ($boolIsChild == TRUE)
        {
            $intId = $intElemId;
        }
        else
        {
            if ($strPastePos == 'pasteAfter')
            {
                $objElem = $this->_objDatabase->getContentObject($intElemId);
                $intId = $objElem->pid;
            }
        }

        while ($objXml->read())
        {
            switch ($objXml->nodeType)
            {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'content':
                            $this->createContent($objXml, $objXml->getAttribute("table"), $strPastePos, $intLastInsertId, TRUE);
                            break;

                        case 'row':
                            $objDb = $this->_objDatabase->insertInto($strTable, $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild));
                            $intLastInsertId = $objDb->insertId;
                            break;
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'article':
                            return;
                            break;
                    }
                    break;
            }
        }
    }

    /**
     * Create Content elements
     * 
     * @param XMLReader $objXml
     * @param string $strTable
     * @param string $strPastePos
     * @param integer $intElemId
     * @param bool $boolIsChild
     */
    protected function createContent(&$objXml, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE)
    {
        if ($boolIsChild == TRUE)
        {
            $intId = $intElemId;
        }
        else
        {
            if ($strPastePos == 'pasteAfter')
            {
                $objElem = $this->_objDatabase->getContentObject($intElemId);
                $intId = $objElem->pid;
            }
        }

        while ($objXml->read())
        {
            switch ($objXml->nodeType)
            {
                case XMLReader::ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'row':
                            $arrSet = $this->createArrSetForRow($objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild);
                            if (array_key_exists('type', $arrSet))
                            {
                                if ($this->_objHelper->existsContentType($arrSet))
                                {
                                    if (md5($GLOBALS['TL_CONFIG']['encryptionKey']) != $this->_strEncryptionKey)
                                    {
                                        if (!array_key_exists(substr($arrSet['type'], 1, -1), $GLOBALS['TL_CTE']['includes']))
                                        {
                                            $this->_objDatabase->insertInto($strTable, $arrSet);
                                        }
                                        else
                                        {
                                            $this->log('Clipboard skip the paste from contentelement because it is an includeElement', __FUNCTION__, TL_GENERAL);
                                        }
                                    }
                                    else
                                    {
                                        $this->_objDatabase->insertInto($strTable, $arrSet);
                                    }
                                }
                                else
                                {
                                    $this->log('Clipboard skip the paste from contentelement because element type dosn`t exists in this system', __FUNCTION__, TL_GENERAL);
                                }
                            }
                    }
                    break;
                case XMLReader::END_ELEMENT:
                    switch ($objXml->localName)
                    {
                        case 'content':
                            return;
                            break;
                    }
                    break;
            }
        }
    }

    /**
     * Create array set for insert query
     * 
     * @param XMLReader $objXml
     * @param integer $intId
     * @param string $strTable
     * @param string $strPastePos
     * @param integer $intElemId
     * @param bool $boolIsChild
     * @return array
     */
    protected function createArrSetForRow(&$objXml, $intId, $strTable, $strPastePos, $intElemId, $boolIsChild = FALSE)
    {
        $arrFields = $this->_objHelper->getFields($strTable);
        $arrSet = array();
        $strFieldType = '';
        $strFieldName = '';

        while ($objXml->read())
        {
            switch ($objXml->nodeType)
            {
                case XMLReader::CDATA:
                case XMLReader::TEXT:
                    if (in_array($strFieldName, $arrFields))
                    {
                        switch ($strFieldName)
                        {
                            case 'pid':
                            case 'id':
                                break;

                            case 'sorting':
                                if ($boolIsChild == TRUE)
                                {
                                    $arrSet['pid'] = $intId;
                                }
                                else
                                {
                                    $arrSorting = $this->_objHelper->getNewPosition($strTable, $intElemId, $strPastePos);
                                    $arrSet['pid'] = $arrSorting['pid'];
                                    $arrSet['sorting'] = $arrSorting['sorting'];
                                    break;
                                }

                            default:
                                switch ($strFieldType)
                                {
                                    case 'default':
                                        $strValue = str_replace($this->_objHelper->arrReplaceWith, $this->_objHelper->arrSearchFor, $objXml->value);
                                        $arrSet[$strFieldName] = $strValue;
                                        break;

                                    default:
                                        $arrSet[$strFieldName] = $objXml->value;
                                        break;
                                }
                                break;
                        }
                    }
                case XMLReader::ELEMENT:
                    if ($objXml->localName == 'field')
                    {
                        $strFieldName = $objXml->getAttribute("name");
                        $strFieldType = $objXml->getAttribute("type");
                    }
                    break;

                case XMLReader::END_ELEMENT:
                    if ($objXml->localName == 'row')
                    {
                        return $arrSet;
                    }
                    break;
            }
        }
    }

    /**
     * Return all metainformation for all xml files from current user
     * 
     * @param string $strDo
     * @return array
     */
    public function getDetailFileInfo($strFilePath)
    {
        $arrMetaInformation = array();
        
        $objDomDoc = new DOMDocument();
        $objDomDoc->load($strFilePath);
        $objMetaTags = $objDomDoc->getElementsByTagName('metatags')->item(0);            
        $objMetaChilds = $objMetaTags->childNodes;

        for($i = 0; $i < $objMetaChilds->length; $i++)
        {
            $strNodeName = $objMetaChilds->item($i)->nodeName;
            $arrMetaInformation[$strNodeName] = $objMetaChilds->item($i)->nodeValue;
        }
        return $arrMetaInformation;
    }    

}

?>