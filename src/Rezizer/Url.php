<?php

namespace Rezizer;


class Url {
    private $serverUrl = null;
    private $secret = null;
    private $operations = [];
    private $rawImageUrl = null;

    private $concatenatedOperations = ['tint', 'background', 'blur', 'format', 'max-age', 'max-kb', 'overlay', 'quality', 'rotate', 'align', 'retina'];

    private $simpleOperations = ['distort', 'extend', 'fit', 'fit-in', 'flip', 'flop', 'tile',
                              'grayscale', 'invert', 'map', 'max', 'min', 'progressive', 'round'];


	public function __construct($serverUrl = null, $secret = null) {
        $this->serverUrl = $serverUrl;
        $this->secret = $secret;
	}


    private function buildPath() {
        $parts = [];

        if (isset($this->operations['tile'])) {
            $parts[] = 'tile';
            return implode($parts, '/');
        }

        if (isset($this->operations['map'])) {
            $parts[] = 'map';
            return implode($parts, '/');
        }

        if (isset($this->operations['palette'])) {
            $command = 'palette';
            if ($this->operations['palette'] !== true && !empty($this->operations['palette'])) {
                $command.= ':' . $this->operations['palette'];
            }
            $parts[] = $command;
            return implode($parts, '/');
        }

        foreach($this->operations as $operation => $value) {
            if (in_array($operation, ['retina'])) {
                continue;
            } else if ($operation === 'crop') {
                $parts[] = implode($value, ',');
            } else if ($operation === 'resize') {
                $command = implode($value, 'x');
                if (array_key_exists('retina', $this->operations)) {
                    $command.= '@' . $this->operations['retina'] . 'x';
                }

                $parts[] = $command;
            } else if ($operation === 'align') {
                $alignment = strtolower($value);

                switch ($alignment) {
                    case 'top':
                        $alignment = 'north';
                        break;
                    case 'left':
                        $alignment = 'weast';
                        break;
                    case 'right':
                        $alignment = 'east';
                        break;
                    case 'bottom':
                        $alignment = 'south';
                        break;
                    case 'north':
                    case 'east':
                    case 'south':
                    case 'west':
                    case 'northeast':
                    case 'southeast':
                    case 'southwest':
                    case 'northwest':
                    case 'middle':
                    case 'center':
                    case 'smart':
                        break;
                }

                $parts[] = $alignment;

            } else if ($operation === 'face') {
                $command = 'face';
                if ($value === 'focused') {
                    $command.= ':focused';
                }
                $parts[] = $command;
            } else if (in_array($operation, $this->concatenatedOperations)) {
                $parts[] = strtolower($operation) . ':' . $value;
            } else {
                $parts[] = strtolower($operation);
            }
        }

        return implode($parts, '/');
    }


    private function generateHash() {
        if (!$this->secret) {
            return null;
        }

        $hash = hash_hmac('sha1', $this->buildPath(), $this->secret, true);
        $hash = base64_encode($hash);
        $hash = str_replace('+', '-', $hash);
        $hash = str_replace('/', '_', $hash);

        return $hash;
    }


    public function generate() {
        $path = $this->buildPath();

        if ($this->secret) {
            $path = $this->generateHash() . '/' . $path;
        }
        $path .= '/' . $this->rawImageUrl;

        return $this->serverUrl . '/' . $path;
    }


    public function with($url = null) {
        $this->rawImageUrl = $url;

        return $this;
    }


    public function resize($h = 0, $w = 0) {
        if (!is_numeric($h) || !is_numeric($w)) {
            throw new Error('Either the height or the width are not valid integers. width:' . $w . ' height:' . $h);
        }
        $this->operations['resize'] = [$w, $h];

        return $this;
    }


    public function crop($top = 0, $left = 0, $bottom = 0, $right = 0) {
        if (!is_numeric($top) || !is_numeric($left) || !is_numeric($bottom) || !is_numeric($right)) {
            throw new Error('At least one of top, left, bottom or right are not valid integers');
        }
        $this->operations['crop'] = [$top, $left, $bottom, $right];

        return $this;
    }

    public function overlay($url = null, $align = 'center') {
        $this->operations['overlay'] = $url . ':' . $align;

        return $this;
    }

    public function faceDetection($inFocus = null) {
        $this->operations['face'] = $inFocus ? 'focused' : true;

        return $this;
    }


    public function smart() {
        $this->operations['align'] = 'smart';

        return $this;
    }


    public function __call($method = null, $values = []) {
        if ($method === 'fitIn') {
            $method = 'fit-in';
        } else if ($method === 'maxKb') {
            $method = 'max-kb';
        } else if ($method === 'maxAge') {
            $method = 'max-age';
        }

        foreach ($this->simpleOperations as $operation) {
            if ($operation === $method) {
                $this->operations[$method] = true;
                return $this;
            }
        }

        foreach ($this->concatenatedOperations as $operation) {
            if ($method === 'overlay') {
                continue;
            }
            if ($operation === $method) {
                $this->operations[$method] = preg_replace('/[^0-9a-zA-Z,\.]/', '', implode($values, ','));
                return $this;
            }
        }
    }

    public function __toString() {
        return $this->generate();
    }
}
