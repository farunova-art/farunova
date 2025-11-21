<?php

/**
 * Input Validator Class
 * Provides validation methods for user inputs
 * 
 * @package FARUNOVA
 * @version 1.0
 */

class Validator
{
    private $errors = [];

    /**
     * Validate required field
     * 
     * @param string $field Field name
     * @param string $value Field value
     * @return bool True if valid
     */
    public function required($field, $value)
    {
        if (empty(trim($value))) {
            $this->errors[$field] = ucfirst($field) . ' is required';
            return false;
        }

        return true;
    }

    /**
     * Validate email
     * 
     * @param string $field Field name
     * @param string $value Email value
     * @return bool True if valid
     */
    public function email($field, $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Invalid email format';
            return false;
        }

        return true;
    }

    /**
     * Validate minimum length
     * 
     * @param string $field Field name
     * @param string $value Field value
     * @param int $min Minimum length
     * @return bool True if valid
     */
    public function minLength($field, $value, $min)
    {
        if (strlen($value) < $min) {
            $this->errors[$field] = ucfirst($field) . ' must be at least ' . $min . ' characters';
            return false;
        }

        return true;
    }

    /**
     * Validate maximum length
     * 
     * @param string $field Field name
     * @param string $value Field value
     * @param int $max Maximum length
     * @return bool True if valid
     */
    public function maxLength($field, $value, $max)
    {
        if (strlen($value) > $max) {
            $this->errors[$field] = ucfirst($field) . ' must not exceed ' . $max . ' characters';
            return false;
        }

        return true;
    }

    /**
     * Validate integer
     * 
     * @param string $field Field name
     * @param string $value Field value
     * @return bool True if valid
     */
    public function integer($field, $value)
    {
        if (!is_numeric($value) || intval($value) != $value) {
            $this->errors[$field] = ucfirst($field) . ' must be an integer';
            return false;
        }

        return true;
    }

    /**
     * Validate numeric
     * 
     * @param string $field Field name
     * @param string $value Field value
     * @return bool True if valid
     */
    public function numeric($field, $value)
    {
        if (!is_numeric($value)) {
            $this->errors[$field] = ucfirst($field) . ' must be numeric';
            return false;
        }

        return true;
    }

    /**
     * Validate range
     * 
     * @param string $field Field name
     * @param int $value Field value
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return bool True if valid
     */
    public function range($field, $value, $min, $max)
    {
        if ($value < $min || $value > $max) {
            $this->errors[$field] = ucfirst($field) . ' must be between ' . $min . ' and ' . $max;
            return false;
        }

        return true;
    }

    /**
     * Validate in array
     * 
     * @param string $field Field name
     * @param string $value Field value
     * @param array $values Allowed values
     * @return bool True if valid
     */
    public function inArray($field, $value, $values)
    {
        if (!in_array($value, $values)) {
            $this->errors[$field] = ucfirst($field) . ' is invalid';
            return false;
        }

        return true;
    }

    /**
     * Validate URL
     * 
     * @param string $field Field name
     * @param string $value URL value
     * @return bool True if valid
     */
    public function url($field, $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = ucfirst($field) . ' must be a valid URL';
            return false;
        }

        return true;
    }

    /**
     * Validate phone number
     * 
     * @param string $field Field name
     * @param string $value Phone number
     * @return bool True if valid
     */
    public function phone($field, $value)
    {
        if (!preg_match('/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/', $value)) {
            $this->errors[$field] = ucfirst($field) . ' is invalid';
            return false;
        }

        return true;
    }

    /**
     * Validate date
     * 
     * @param string $field Field name
     * @param string $value Date value
     * @param string $format Date format (default: Y-m-d)
     * @return bool True if valid
     */
    public function date($field, $value, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $value);
        if (!$d || $d->format($format) !== $value) {
            $this->errors[$field] = ucfirst($field) . ' must be in format ' . $format;
            return false;
        }

        return true;
    }

    /**
     * Validate matches another field
     * 
     * @param string $field Field name
     * @param string $value Field value
     * @param string $otherField Other field to match
     * @param array $data Form data array
     * @return bool True if valid
     */
    public function matches($field, $value, $otherField, $data)
    {
        if ($value !== ($data[$otherField] ?? '')) {
            $this->errors[$field] = ucfirst($field) . ' must match ' . ucfirst($otherField);
            return false;
        }

        return true;
    }

    /**
     * Validate unique in database
     * 
     * @param string $field Field name
     * @param string $value Field value
     * @param string $table Database table
     * @param string $column Column name
     * @param mysqli $conn Database connection
     * @return bool True if valid
     */
    public function unique($field, $value, $table, $column, $conn)
    {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table WHERE $column = ?");
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            $this->errors[$field] = ucfirst($field) . ' already exists';
            return false;
        }

        return true;
    }

    /**
     * Get all errors
     * 
     * @return array Array of errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get error for field
     * 
     * @param string $field Field name
     * @return string|null Error message or null
     */
    public function getError($field)
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Check if valid (no errors)
     * 
     * @return bool True if valid
     */
    public function isValid()
    {
        return empty($this->errors);
    }

    /**
     * Add custom error
     * 
     * @param string $field Field name
     * @param string $message Error message
     * @return void
     */
    public function addError($field, $message)
    {
        $this->errors[$field] = $message;
    }

    /**
     * Clear all errors
     * 
     * @return void
     */
    public function clearErrors()
    {
        $this->errors = [];
    }
}
