<?php
/**
 * Customized factory for record driver data formatting view helper
 *
 * PHP version 7
 *
 * Copyright (C) Michael Bikner 2021.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
namespace DbSbg\View\Helper\Root;

/**
 * Factory for record driver data formatting view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Michael Birkner <birkner_michael@yahoo.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 * Wiki
 */
class RecordDataFormatterFactory
  extends \VuFind\View\Helper\Root\RecordDataFormatterFactory
{
    
    /**
     * Get default specifications for displaying data in collection-info metadata.
     *
     * @return array
     */
    public function getDefaultCollectionInfoSpecs()
    {
        $spec = new \VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder();

        $spec->setMultiLine(
            'Authors', 'getDeduplicatedAuthors', $this->getAuthorFunction()
        );
        $spec->setLine('Summary', 'getSummary');
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        // DbSbg: Removed the default display of the language of the record as this
        // does not translate the language name. Now using more arguments in
        // "setLine" method for translating the language name(s) in the records
        // "core" view (= detail view of a record). See also pull request 413 at
        // VuFind GitHub and there especially the "Files changed" section to get an
        // example of the code used here:
        // https://github.com/vufind-org/vufind/pull/413
        $spec->setLine(
          'Language', 'getLanguages', null,
          ['translate' => true, 'translationTextDomain' => 'Languages::',
           'itemPrefix' => '<span property="availableLanguage" typeof="Language">'
                           . '<span property="name">',
           'itemSuffix' => '</span></span>']
        );
        $spec->setTemplateLine(
            'Published', 'getPublicationDetails', 'data-publicationDetails.phtml'
        );
        $spec->setLine(
            'Edition', 'getEdition', null,
            ['itemPrefix' => '<span property="bookEdition">',
             'itemSuffix' => '</span>']
        );
        $spec->setTemplateLine('Series', 'getSeries', 'data-series.phtml');
        $spec->setTemplateLine(
            'Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml'
        );
        $spec->setTemplateLine('Online Access', true, 'data-onlineAccess.phtml');
        $spec->setTemplateLine(
            'Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml'
        );
        $spec->setLine('Notes', 'getGeneralNotes');
        $spec->setLine('Production Credits', 'getProductionCredits');
        $spec->setLine(
            'ISBN', 'getISBNs', null,
            ['itemPrefix' => '<span property="isbn">', 'itemSuffix' => '</span>']
        );
        $spec->setLine(
            'ISSN', 'getISSNs', null,
            ['itemPrefix' => '<span property="issn">', 'itemSuffix' => '</span>']
        );
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in collection-record metadata.
     *
     * @return array
     */
    public function getDefaultCollectionRecordSpecs()
    {
        $spec = new \VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder();

        $spec->setLine('Summary', 'getSummary');
        $spec->setMultiLine(
            'Authors', 'getDeduplicatedAuthors', $this->getAuthorFunction()
        );
        // DbSbg: Removed the default display of the language of the record as this
        // does not translate the language name. Now using more arguments in
        // "setLine" method for translating the language name(s) in the records
        // "core" view (= detail view of a record). See also pull request 413 at
        // VuFind GitHub and there especially the "Files changed" section to get an
        // example of the code used here:
        // https://github.com/vufind-org/vufind/pull/413
        $spec->setLine(
          'Language', 'getLanguages', null,
          ['translate' => true, 'translationTextDomain' => 'Languages::',
           'itemPrefix' => '<span property="availableLanguage" typeof="Language">'
                           . '<span property="name">',
           'itemSuffix' => '</span></span>']
        );
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setLine('Access', 'getAccessRestrictions');
        $spec->setLine('Related Items', 'getRelationshipNotes');
        return $spec->getArray();
    }

    /**
     * Get default specifications for displaying data in core metadata.
     *
     * @return array
     */
    public function getDefaultCoreSpecs()
    {
        $spec = new \VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder();

        $spec->setTemplateLine(
            'Published in', 'getContainerTitle', 'data-containerTitle.phtml'
        );
        $spec->setLine(
            'New Title', 'getNewerTitles', null, ['recordLink' => 'title']
        );
        $spec->setLine(
            'Previous Title', 'getPreviousTitles', null, ['recordLink' => 'title']
        );
        $spec->setMultiLine(
            'Authors', 'getDeduplicatedAuthors', $this->getAuthorFunction()
        );
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        // DbSbg: Removed the default display of the language of the record as this
        // does not translate the language name. Now using more arguments in
        // "setLine" method for translating the language name(s) in the records
        // "core" view (= detail view of a record). See also pull request 413 at
        // VuFind GitHub and there especially the "Files changed" section to get an
        // example of the code used here:
        // https://github.com/vufind-org/vufind/pull/413
        $spec->setLine(
          'Language', 'getLanguages', null,
          ['translate' => true, 'translationTextDomain' => 'Languages::',
           'itemPrefix' => '<span property="availableLanguage" typeof="Language">'
                           . '<span property="name">',
           'itemSuffix' => '</span></span>']
        );
        $spec->setTemplateLine(
            'Published', 'getPublicationDetails', 'data-publicationDetails.phtml'
        );
        $spec->setLine(
            'Edition', 'getEdition', null,
            ['itemPrefix' => '<span property="bookEdition">',
             'itemSuffix' => '</span>']
        );
        $spec->setTemplateLine('Series', 'getSeries', 'data-series.phtml');
        $spec->setTemplateLine(
            'Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml'
        );
        $spec->setTemplateLine(
            'child_records', 'getChildRecordCount', 'data-childRecords.phtml',
            ['allowZero' => false]
        );
        $spec->setTemplateLine('Online Access', true, 'data-onlineAccess.phtml');
        $spec->setTemplateLine(
            'Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml'
        );
        $spec->setTemplateLine('Tags', true, 'data-tags.phtml');

        // DbSbg: Show AC-Number
        $spec->setLine('ACNo', 'getAcNo');

        return $spec->getArray();
    }

}
