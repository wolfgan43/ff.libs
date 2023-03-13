<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package VGallery
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace ff\libs\gui\components;

use ff\libs\dto\DataHtml;
use ff\libs\dto\DataResponse;
use ff\libs\Exception;
use ff\libs\storage\dto\OrmResults;
use ff\libs\storage\Model;

/**
 * Class Record
 * @package ff\libs\gui\components
 */
class Form
{
    protected const CONVERT_BY_TYPE     = [
                                            "image"     => [
                                                "callback"  => "imageTag",
                                                "width"     => 50,
                                                "height"    => 50,
                                                "default"   => "noimg"
                                            ],
                                            "datetime"  => [],
                                            "date"      => [],
                                            "time"      => [],
                                            "week"      => [],
                                            "currency"  => []
                                        ];
    private string $id;
    private ?Model $model;
    private string $recordKey;
    private int|string $recordValue;

    private array $record;
    private array $fields;
    private string $template = '<form method="post" action="{action}"><h3>{title}</h3>{fields}</form>';

    /**
     * Form constructor.
     * @param string|null $modelName
     * @throws Exception
     */
    public function __construct(string $modelName = null)
    {
        $this->setModel($modelName);
    }


    /**
     * @param array $where
     * @param string|null $modelName
     * @return $this
     * @throws Exception
     */
    public function loadFromOrm(array $where, string $modelName = null) : self
    {
        $this->setModel($modelName);
        if (empty($this->model)) {
            throw new Exception("Model not defined");
        }

        $record                 = $this->model->readOne($where);
        $this->record           = $record->getArray(0);
        $this->recordKey        = $record->getPrimaryKey();
        $this->recordValue      = $record->key(0);

        return $this;
    }
    public function loadFromApi(array $params) : self
    {
        return $this;
    }
    public function loadFromArray(array $data) : self
    {
        $this->record = $data;

        return $this;
    }
    public function display() : DataHtml
    {
        $html = new DataHtml();
        $html->html = $this->model->getForm($this->record);
        return $html;
    }
    /**
     * @return DataResponse
     * @throws Exception
     */
    public function submit() : DataResponse
    {
        return new DataResponse(
            (empty($this->recordKey)
            ? $this->model->insert(Kernel::$Page->getRawData())
            : $this->model->update([$this->recordValue => $this->recordKey], Kernel::$Page->getRawData())
        )->getArray(0));
    }

    /**
     * @param string|null $modelName
     * @return void
     * @throws Exception
     */
    private function setModel(string $modelName = null) : void
    {
        if (!empty($modelName)) {
            $this->model                                    = new Model($modelName);
            $schema                                         = $this->model->schema(static::CONVERT_BY_TYPE);
            $this->fields                                   = $this->setFields($schema->properties);
        }
    }

    private function setFields(array $properties) : array
    {
        $fields = [];
        foreach ($properties as $name => $property) {
            $fields[$name] = $property;
        }
        return $fields;
    }
}
