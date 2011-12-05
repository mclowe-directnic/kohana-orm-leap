<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Copyright 2011 Spadefoot
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * This class builds an SQL update statement.
 *
 * @package Leap
 * @category SQL
 * @version 2011-11-07
 *
 * @abstract
 */
abstract class Base_DB_SQL_Update_Builder extends DB_SQL_Builder {

	/**
	 * This variable stores the name of the SQL dialect being used.
	 *
	 * @access protected
	 * @var string
	 */
	protected $dialect = NULL;

	/**
	 * This variable stores a reference to the helper class that implements the expression
	 * interface.
	 *
	 * @access protected
	 * @var DB_SQL_Expression_Interface
	 */
	protected $helper = NULL;

    /**
     * This variable stores the build data for the SQL statement.
     *
     * @access protected
     * @var array
     */
    protected $data = NULL;

    /**
     * This constructor instantiates this class using the specified dialect.
     *
     * @access public
     * @param string $dialect 					the SQL statement to be used
     */
    public function __construct($dialect) {
		$this->dialect = $dialect;
		$helper = 'DB_' . $dialect . '_Expression';
		$this->helper = new $helper();
        $this->data = array();
        $this->data['table'] = NULL;
        $this->data['column'] = array();
        $this->data['where'] = array();
        $this->data['order_by'] = array();
        $this->data['limit'] = 0;
        $this->data['offset'] = 0;
    }

    /**
     * This function sets which table will be modified.
     *
     * @access public
     * @param string $table                     the database table to be modified
     * @return DB_SQL_Update_Builder            a reference to the current instance
     */
    public function table($table) {
        $table = $this->helper->prepare_identifier($table);
        $this->data['table'] = $table;
        return $this;
    }

    /**
     * This function sets the associated value with the specified column.
     *
     * @access public
     * @param string $column                    the column to be set
     * @param string $value                     the value to be set
     * @return DB_SQL_Update_Builder            a reference to the current instance
     */
    public function set($column, $value) {
        $column = $this->helper->prepare_identifier($column);
        $value = $this->helper->prepare_value($value);
        $this->data['column'][$column] = "{$column} = {$value}";
        return $this;
    }

    /**
     * This function either opens or closes a "where" group.
     *
     * @access public
     * @param string $parenthesis               the parenthesis to be used
     * @param string $connector                 the connector to be used
     * @return DB_SQL_Update_Builder            a reference to the current instance
     */
    public function where_block($parenthesis, $connector = 'AND') {
        $parenthesis = $this->helper->prepare_parenthesis($parenthesis);
        $connector = $this->helper->prepare_connector($connector);
        $this->data['where'][] = array($connector, $parenthesis);
        return $this;
    }

    /**
     * This function adds a "where" constraint.
     *
     * @access public
     * @param string $column                    the column to be constrained
     * @param string $operator                  the operator to be used
     * @param string $value                     the value the column is constrained with
     * @param string $connector                 the connector to be used
     * @return DB_SQL_Update_Builder            a reference to the current instance
     * @throws Kohana_SQL_Exception             indicates an invalid SQL build instruction
     */
	public function where($column, $operator, $value, $connector = 'AND') {
	    $operator = $this->helper->prepare_operator('COMPARISON', $operator);
	    if (($operator == DB_SQL_Operator::_BETWEEN_) || ($operator == DB_SQL_Operator::_NOT_BETWEEN_)) {
	        if (!is_array($value)) {
                throw new Kohana_SQL_Exception('Message: Invalid build instruction. Reason: Operator requires the value to be declared as an array.', array(':column' => $column, ':operator' => $operator, ':value' => $value, ':connector' => $connector));
	        }
	        $column = $this->helper->prepare_identifier($column);
    	    $value0 = $this->helper->prepare_value($value[0]);
    	    $value1 = $this->helper->prepare_value($value[1]);
            $connector = $this->helper->prepare_connector($connector);
            $this->data['where'][] = array($connector, "{$column} {$operator} {$value0} AND {$value1}");
	    }
	    else {
	        if ((($operator == DB_SQL_Operator::_IN_) || ($operator == DB_SQL_Operator::_NOT_IN_)) && !is_array($value)) {
                throw new Kohana_SQL_Exception('Message: Invalid build instruction. Reason: Operator requires the value to be declared as an array.', array(':column' => $column, ':operator' => $operator, ':value' => $value, ':connector' => $connector));
            }
            if (is_null($value)) {
                switch ($operator) {
                    case DB_SQL_Operator::_EQUAL_TO_:
                        $operator = DB_SQL_Operator::_IS_;
                    break;
                    case DB_SQL_Operator::_NOT_EQUIVALENT_:
                        $operator = DB_SQL_Operator::_IS_NOT_;
                    break;
                }
            }
	        $column = $this->helper->prepare_identifier($column);
    	    $value = $this->helper->prepare_value($value);
            $connector = $this->helper->prepare_connector($connector);
            $this->data['where'][] = array($connector, "{$column} {$operator} {$value}");
	    }
		return $this;
	}

    /**
     * This function sorts a column either ascending or descending order.
     *
     * @access public
     * @param string $column                    the column to be sorted
     * @param boolean $descending               whether to sort in descending order
     * @return DB_SQL_Update_Builder            a reference to the current instance
     */
	public function order_by($column, $descending = FALSE, $nulls = 'DEFAULT') {
	    $column = $this->helper->prepare_identifier($column);
	    $descending = $this->helper->prepare_boolean($descending);
		$this->data['order_by'][] = "{$column} " . (($descending) ? 'DESC' : 'ASC');
		return $this;
	}

    /**
     * This function sets a "limit" constraint on the statement.
     *
     * @access public
     * @param integer $limit                    the "limit" constraint
     * @return DB_SQL_Update_Builder            a reference to the current instance
     */
	public function limit($limit) {
	    $limit = $this->helper->prepare_natural($limit);
		$this->data['limit'] = $limit;
		return $this;
	}

    /**
     * This function sets an "offset" constraint on the statement.
     *
     * @access public
     * @param integer $offset                   the "offset" constraint
     * @return DB_SQL_Update_Builder            a reference to the current instance
     */
    public function offset($offset) {
	    $offset = $this->helper->prepare_natural($offset);
        $this->data['offset'] = $offset;
        return $this;
    }

}
?>