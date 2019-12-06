<?php
namespace NoreSources\SQL\Expression;

use NoreSources\SQL as sql;
use NoreSources\Expression as xpr;

interface Expression extends xpr\Expression, sql\Tokenizable
{
}