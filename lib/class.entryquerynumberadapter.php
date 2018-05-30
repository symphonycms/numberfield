<?php

/**
 * @package toolkit
 */
/**
 * Specialized EntryQueryFieldAdapter that facilitate creation of queries filtering/sorting data from
 * an textarea Field.
 * @see FieldNumber
 * @since Symphony 3.0.0
 */
class EntryQueryNumberAdapter extends EntryQueryFieldAdapter
{
    public function isFilterBetween($filter)
    {
        return preg_match('/^between:? ?(-?(?:\d+(?:\.\d+)?|\.\d+)) and (-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $filter);
    }

    public function createFilterBetween($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = $this->field->cleanValue($filter);
        $matches = [];
        preg_match('/^between:? ?(-?(?:\d+(?:\.\d+)?|\.\d+)) and (-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $filter, $matches);

        $conditions = [];
        foreach ($columns as $key => $col) {
            $conditions[] = [$this->formatColumn($col, $field_id) => ['between' => [(int)$matches[1], (int)$matches[2]]]];
        }
        if (count($conditions) < 2) {
            return $conditions;
        }
        return ['or' => $conditions];
    }

    public function isFilterEqualLesserGreater($filter)
    {
        return preg_match('/^(equal to or )?(less|greater) than\s*(-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $filter);
    }

    public function createFilterEqualLesserGreater($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = $this->field->cleanValue($filter);
        $matches = [];
        preg_match('/^(equal to or )?(less|greater) than\s*(-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $filter, $matches);

        switch($matches[2]) {
            case 'less':
                $expression .= '<';
                break;

            case 'greater':
                $expression .= '>';
                break;
        }

        if($matches[1]){
            $expression .= '=';
        }

        $conditions = [];
        foreach ($columns as $key => $col) {
            $conditions[] = [$this->formatColumn($col, $field_id) => [$expression => (int)$matches[3]]];
        }
        if (count($conditions) < 2) {
            return $conditions;
        }
        return ['or' => $conditions];
    }

    public function isFilterSymbol($filter)
    {
        return preg_match('/^(=?[<>]=?)\s*(-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $filter);
    }

    public function createFilterSymbol($filter, array $columns)
    {
        $field_id = General::intval($this->field->get('id'));
        $filter = $this->field->cleanValue($filter);
        $matches = [];
        preg_match('/^(=?[<>]=?)\s*(-?(?:\d+(?:\.\d+)?|\.\d+))$/i', $filter, $matches);

        $conditions = [];
        foreach ($columns as $key => $col) {
            $conditions[] = [$this->formatColumn($col, $field_id) => [$matches[1] => (int)$matches[2]]];
        }
        if (count($conditions) < 2) {
            return $conditions;
        }
        return ['or' => $conditions];
    }

    /**
     * @see EntryQueryFieldAdapter::filterSingle()
     *
     * @param EntryQuery $query
     * @param string $filter
     * @return array
     */
    protected function filterSingle(EntryQuery $query, $filter)
    {
        General::ensureType([
            'filter' => ['var' => $filter, 'type' => 'string'],
        ]);
        if ($this->isFilterBetween($filter)) {
            return $this->createFilterBetween($filter, $this->getFilterColumns());
        } elseif ($this->isFilterEqualLesserGreater($filter)) {
            return $this->createFilterEqualLesserGreater($filter, $this->getFilterColumns());
        } elseif ($this->isFilterSymbol($filter)) {
            return $this->createFilterSymbol($filter, $this->getFilterColumns());
        }
        return $this->createFilterEquality($filter, $this->getFilterColumns());
    }
}
