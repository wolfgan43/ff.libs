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
namespace phpformsframework\libs\gui\components;

/**
 * Class DataTablePlugin
 * @package phpformsframework\libs\gui\components
 */
class DataTablePlugin extends DataTable
{
    protected const CSS     = [
        "https://db2.creo.it/DataTables-1.10.21/css/datatables.css"
    ];

    protected const JS      = [
        "https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js",
        "https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"
    ];


    /**
     * @return string
     */
    protected function html() : string
    {
        $order = [];
        foreach ($this->sort ?? [] as $sort => $dir) {
            $order[] = [$sort, $dir];
        }

        $search = (
            $this->search
            ? ["search" => $this->search]
            : []
        );

        foreach ($this->sort ?? [] as $sort => $dir) {
            $order[] = [$sort, $dir];
        }

        $columns = null;
        $columns_json = [];
        foreach ($this->columns as $column) {
            $columns                    .= '<th>' . $column . '</th>';
            $columns_json[]["data"]     = $column;
        }

        $rows = null;
        foreach ($this->dataTable->toArray() as $record) {
            $rows                       .= '<tr><td>' . implode('</td><td>', $record). '</td></tr>';
        }

        $this->jsEmbed($search, $order, $columns_json);

        return '<table class="dt-table" data-component="' . $this->getName() . '">
            <thead>
                <tr>' . $columns . '</tr>
            </thead>
            <tbody>
                ' . $rows . '
            </tbody>
        </table>';
    }

    private function jsEmbed(array $search, array $order, array $columns) : void
    {
        $this->js_embed = 'setTimeout(function() {
            $(".dt-table")
            .on("preXhr.dt", function ( e, settings, data ) {                                                
                let url = new URL(window.location);
                
                url.searchParams.set("page", Math.floor( data.start / data.length ) + 1);
                url.searchParams.set("length", data.length);

                for(let i = 0; i < settings.aLastSort.length; i++) {
                    url.searchParams.delete("sort[" + settings.aLastSort[i].col + "]");
                }
                for(let i = 0; i < data.order.length; i++) {
                    url.searchParams.set("sort[" + data.order[i].column + "]", data.order[i].dir);
                }
                
                if(data.search.value.length) {
                    url.searchParams.set("search", data.search.value);
                } else {
                    url.searchParams.delete("search");
                }
                
                window.history.pushState({}, "", url);
                
                url.searchParams.set("component", $(this).data("component"));
                
                settings.ajax.url = url.toString();
            })
            .on("xhr.dt", function ( e, settings, json, xhr ) {
                if ( xhr.status === 204 || xhr.responseText === "null" ) {
                    json = {};
                }
            })
            .DataTable({
                processing: true,
                serverSide: true,
                search: ' . json_encode($search) . ',
                order: ' . json_encode($order) . ',
                displayStart: ' . $this->start . ',
                pageLength: ' . $this->length . ',
                ajax: {
                    url: window.location.href,
                    type: "POST",
                    dataType : "json"
                },
                columns: ' . json_encode($columns). ',
                deferLoading: ' . $this->records_filtered . '
            });
        }, 1000);';
    }
}
