<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPInterface.php to edit this template
 */

namespace Common\View;

/**
 *
 * @author Administrator
 */
interface IView
{
    //il json viene aggiunto al WindowState sia in Client e sia in Server
    
    public function Client() : void;
    
    public function Server() : void;
}
