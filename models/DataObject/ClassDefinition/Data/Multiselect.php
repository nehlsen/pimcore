<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

class Multiselect extends Data implements
    ResourcePersistenceAwareInterface,
    QueryResourcePersistenceAwareInterface,
    TypeDeclarationSupportInterface,
    EqualComparisonInterface,
    VarExporterInterface,
    \JsonSerializable,
    NormalizerInterface,
    LayoutDefinitionEnrichmentInterface,
    FieldDefinitionEnrichmentInterface,
    DataContainerAwareInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\Traits\SimpleNormalizerTrait;
    use DataObject\ClassDefinition\DynamicOptionsProvider\SelectionProviderTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'multiselect';

    /**
     * Available options to select
     *
     * @internal
     *
     * @var array|null
     */
    public $options;

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public $height = 0;

    /**
     * @internal
     *
     * @var int|null
     */
    public $maxItems;

    /**
     * @internal
     *
     * @var string|null
     */
    public $renderType;

    /**
     * Options provider class
     *
     * @internal
     *
     * @var string|null
     */
    public $optionsProviderClass;

    /**
     * Options provider data
     *
     * @internal
     *
     * @var string|null
     */
    public $optionsProviderData;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'text';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'text';

    /**
     * @internal
     */
    public bool $dynamicOptions = false;

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @return string|int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param string|int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
        $this->height = $height;

        return $this;
    }

    /**
     * @param int|null $maxItems
     *
     * @return $this
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = $this->getAsIntegerCast($maxItems);

        return $this;
    }

    /**
     * @return int|null
     */
    public function getMaxItems()
    {
        return $this->maxItems;
    }

    /**
     * @param string|null $renderType
     *
     * @return $this
     */
    public function setRenderType($renderType)
    {
        $this->renderType = $renderType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRenderType()
    {
        return $this->renderType;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param array|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            return implode(',', $data);
        }

        return null;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        if (strlen((string) $data)) {
            return explode(',', $data);
        }

        return null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        if (!empty($data) && is_array($data)) {
            return ','.implode(',', $data).',';
        }

        return null;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param array|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            return implode(',', $data);
        }

        return null;
    }

    /**
     * @param array $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT
        );

        if ($optionsProvider === null) {
            return $this->getDataForEditmode($data, $object, $params);
        }

        $context = $params['context'] ?? [];
        $context['object'] = $object;
        if ($object) {
            $context['class'] = $object->getClass();
        }

        $context['fieldname'] = $this->getName();
        $options = $optionsProvider->{'getOptions'}($context, $this);
        $this->setOptions($options);

        if (isset($params['purpose']) && $params['purpose'] === 'editmode') {
            $result = $data;
        } else {
            $result = ['value' => $data, 'options' => $this->getOptions()];
        }

        return $result;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param array|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        if (is_array($data)) {
            return implode(',', array_map(function ($v) {
                return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
            }, $data));
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Model\Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }

        if (!is_array($data) && !empty($data)) {
            throw new Model\Element\ValidationException('Invalid multiselect data');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            return implode(',', $data);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        $data = $this->getDataFromObjectParam($object, $params);
        if (is_array($data)) {
            return implode(' ', $data);
        }

        return '';
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param  string $value
     * @param  string $operator
     * @param  array $params
     *
     * @return string
     */
    public function getFilterCondition($value, $operator, $params = [])
    {
        $params['name'] = $this->name;

        return $this->getFilterConditionExt(
            $value,
            $operator,
            $params
        );
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param string $value
     * @param string $operator
     * @param array $params optional params used to change the behavior
     *
     * @return string|null
     */
    public function getFilterConditionExt($value, $operator, $params = [])
    {
        if ($operator === '=') {
            $name = $params['name'] ? $params['name'] : $this->name;

            $db = \Pimcore\Db::get();
            $key = $db->quoteIdentifier($name);
            if (!empty($params['brickPrefix'])) {
                $key = $params['brickPrefix'].$key;
            }

            $value = "'%,".$value.",%'";

            return $key.' LIKE '.$value.' ';
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param array|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        if ($data) {
            $map = [];
            foreach ($data as $value) {
                $map[$value] = $value;
            }

            $html = '<ul>';

            foreach ($this->options as $option) {
                if ($map[$option['value']] ?? false) {
                    $value = $option['key'];
                    $html .= '<li>' . $value . '</li>';
                }
            }

            $html .= '</ul>';

            $value = [];
            $value['html'] = $html;
            $value['type'] = 'html';

            return $value;
        } else {
            return '';
        }
    }

    /**
     * @param DataObject\ClassDefinition\Data\Multiselect $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->maxItems = $masterDefinition->maxItems;
        $this->options = $masterDefinition->options;
    }

    /**
     * @return string|null
     */
    public function getOptionsProviderClass()
    {
        return $this->optionsProviderClass;
    }

    /**
     * @param string|null $optionsProviderClass
     */
    public function setOptionsProviderClass($optionsProviderClass)
    {
        $this->optionsProviderClass = $optionsProviderClass;
    }

    /**
     * @return string|null
     */
    public function getOptionsProviderData()
    {
        return $this->optionsProviderData;
    }

    /**
     * @param string|null $optionsProviderData
     */
    public function setOptionsProviderData($optionsProviderData)
    {
        $this->optionsProviderData = $optionsProviderData;
    }

    /**
     * @param array|null $existingData
     * @param array $additionalData
     *
     * @return array
     */
    public function appendData($existingData, $additionalData)
    {
        if (!is_array($existingData)) {
            $existingData = [];
        }

        $existingData = array_unique(array_merge($existingData, $additionalData));

        return $existingData;
    }

    /**
     * @param array|null $existingData
     * @param array $removeData
     *
     * @return array
     */
    public function removeData($existingData, $removeData)
    {
        if (!is_array($existingData)) {
            $existingData = [];
        }

        $existingData = array_unique(array_diff($existingData, $removeData));

        return $existingData;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * @param array|null $value1
     * @param array|null $value2
     *
     * @return bool
     */
    public function isEqual($value1, $value2): bool
    {
        return $this->isEqualArray($value1, $value2);
    }

    /**
     * @return $this
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()// : static
    {
        if ($this->getOptionsProviderClass() && Service::doRemoveDynamicOptions()) {
            $this->options = null;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveBlockedVars(): array
    {
        $blockedVars = parent::resolveBlockedVars();

        if ($this->getOptionsProviderClass()) {
            $blockedVars[] = 'options';
        }

        return $blockedVars;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?array';
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?array';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return 'string[]|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return 'string[]|null';
    }

    /**
     * Perform sanity checks, see #5010.
     *
     * @param mixed $containerDefinition
     * @param array $params
     */
    public function preSave($containerDefinition, $params = [])
    {
        /** @var DataObject\ClassDefinition\DynamicOptionsProvider\MultiSelectOptionsProviderInterface|null $optionsProvider */
        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT
        );
        if ($optionsProvider) {
            $context = [];
            $context['fieldname'] = $this->getName();

            try {
                $options = $optionsProvider->getOptions($context, $this);
            } catch (\Throwable $e) {
                // error from getOptions => no values => no comma => no problems
                $options = null;
            }
        } else {
            $options = $this->getOptions();
        }
        if (is_array($options) && array_reduce($options, static function ($containsComma, $option) {
            return $containsComma || str_contains($option['value'], ',');
        }, false)) {
            throw new \Exception("Field {$this->getName()}: Multiselect option values may not contain commas (,) for now, see <a href='https://github.com/pimcore/pimcore/issues/5010' target='_blank'>issue #5010</a>.");
        }
    }

    /**
     * @param mixed $containerDefinition
     * @param array $params
     */
    public function postSave($containerDefinition, $params = [])
    {
        // nothing to do
    }

    /**
     * { @inheritdoc }
     */
    public function enrichFieldDefinition(/** array */ $context = []) /** : static */
    {
        $this->doEnrichDefinitionDefinition(null, $this->getName(),
            'fielddefinition', DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT, $context);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enrichLayoutDefinition(/* ?Concrete */ $object, /* array */ $context = []) // : static
    {
        $this->doEnrichDefinitionDefinition($object, $this->getName(),
            'layout', DataObject\ClassDefinition\Helper\OptionsProviderResolver::MODE_MULTISELECT, $context);

        return $this;
    }
}
