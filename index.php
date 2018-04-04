<?php
    $jsonInfoPath = "./data.json";
    $file_db;
    init();

    function init(){
        createFileJSON();
        
        if(FacturaSaved()){
            $clienteName = $_POST['clienteName'];
            $date = $_POST['date'];
            insertNuevaFactura($clienteName,$date);
        }
        else if(FacturaUpdated()){
            $clienteName = $_POST['clienteName'];
            $date = $_POST['date'];
            $facturaID = $_POST['facturaID'];
            updatedateAndClienteFromFactura($date,$clienteName,$facturaID);
        }
        else  if(FacturaDeleted()){
            $facturaID = $_POST['removeFactura'];
            deleteFacturaById($facturaID); 
        }
        else if(ProductDeleted()){
            $producto_id = $_POST['producto_id'];
            $result = deleteProductoById($producto_id); 
        }
    }

    function createFileJSON(){
        if(!FileExists()){
            global $jsonInfoPath;
            $file = fopen($jsonInfoPath, "w+");
            $data = array();
            $data['facturas'] = array();
            $data['productos'] = array();
            $json = json_encode($data);
            fwrite($file, $json);
            fclose($file);
        }
    }

    function FileExists(){
        global $jsonInfoPath;
        return file_exists($jsonInfoPath);
    }

    function FacturaSaved(){
       return isset($_POST['date']) && isset($_POST['clienteName']) && isset($_POST['saveFactura']) && !isset($_POST['facturaID']);
    }

    function updateFactura($facturaID,$subtotal){
        global $jsonInfoPath;
        $facturaResult = getFacturaById($facturaID);
        
        $facturaID = (int)$facturaResult['facturaID'];
        $tax = $facturaResult['tax'];
        $total = $facturaResult['total'];
        $subtax = ($subtotal * 0.13);
        $tax = $tax + $subtax;
        $total = $total + ($subtotal + $subtax);
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
    
        foreach($json['facturas'] as &$row){
            if($row['facturaID'] == $facturaID){
                $row['tax'] = $tax;
                $row['total'] = $total;
            }
        }
        $dataUpdated = json_encode($json);
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }

   
    function FacturaOpened(){
        return isset($_POST['openFactura']) || isset($_POST['saveProduct']);
    }
    function FacturaDeleted(){
        return isset($_POST['removeFactura']);
    }
    
    function insertNewProduct(){
        global $jsonInfoPath;
        $facturaID = $_POST['facturaID'];
        $cantidad = $_POST['cantidad'];
        $valorUnitario = $_POST['valorUnitario'];
        $descripcion = $_POST['descripcion'];

        $subtotal = $valorUnitario * $cantidad;
        $last_id = getLastID() + 1;
        $stringData = file_get_contents($jsonInfoPath);
        $data = json_decode($stringData, true);
        array_push($data['productos'], 
                    array("producto_id"=>$last_id,"facturaID"=>$facturaID,"cantidad"=>$cantidad,"descripcion"=>$descripcion,
                         "valorUnitario"=>$valorUnitario,"subtotal"=>$subtotal));
        $json = json_encode($data);
        $file = fopen($jsonInfoPath, "w+");

        fwrite($file, $json);
        fclose($file);
        updateFactura($facturaID,$subtotal);
    }


    function FacturaUpdated(){
        return isset($_POST['date']) && isset($_POST['clienteName']) && isset($_POST['saveFactura']) && isset($_POST['facturaID']);
    }
    function isSaveProductButtonTriggered(){
        return isset($_POST['cantidad']) && isset($_POST['descripcion']) && isset($_POST['valorUnitario']) && isset($_POST['saveProduct']) && isset($_POST['facturaID']);
    }

    function getLastID(){
        global $jsonInfoPath;
        $last_id = 0;
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(empty($json['productos'])){
            return $last_id;
        }
        else{
            foreach($json['productos'] as $row){
                $last_id = (int)$row['producto_id'];
            }
            return $last_id;
        }
    }


   

    function deleteAllProductsByFacturaId($facturaID){
        global $jsonInfoPath;
        
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            $i=0;
            foreach($json['productos'] as $row) {
                if($row['facturaID'] == $facturaID){
                    unset($json['productos'][$i]);
                }
                $i++;
            }
        }
        $json['productos'] = array_values($json['productos']);
        $dataUpdated = json_encode($json);
        //Save the changes into the file
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }
    


    function insertNuevaFactura($clienteName, $date){
        global $jsonInfoPath;
        $tax = 0.00;
        $total = 0.00;
        $last_id = getLastFacturaIdInserted() + 1;
        
        //Get previous data from json data
        $stringData = file_get_contents($jsonInfoPath);
        $data = json_decode($stringData, true);
        
        //Insert new factura in the end facturas array
        array_push($data['facturas'], array("facturaID"=>$last_id,"clienteName"=>$clienteName,"date"=>$date,"tax"=>$tax,"total"=>$total));
        $json = json_encode($data);
        //Save the changes into the file
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $json);
        fclose($file);
    }

    function ProductDeleted(){
        return isset($_POST['remover-producto']);
    }





    function updatedateAndClienteFromFactura($newdate,$newclienteName,$facturaID){
        global $jsonInfoPath;
        $facturaResult = getFacturaById($facturaID);
        
        $facturaID = (int)$facturaResult['facturaID'];
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
    
        foreach($json['facturas'] as &$row){
            if($row['facturaID'] == $facturaID){
                $row['date'] = $newdate;
                $row['clienteName'] = $newclienteName;
            }
        }
        $dataUpdated = json_encode($json);
        //Save the changes into the file
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }


    function getFacturaById($facturaID){
        global $jsonInfoPath;
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['facturas'])){
            foreach($json['facturas'] as $row) {
                if($row['facturaID'] == $facturaID){
                    return $row;
                }
            }
            return array();
        }
    }

    function fillAllReceiptsTable(){
        global $jsonInfoPath;
        
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['facturas'])){
            foreach($json['facturas'] as $row) {
                $facturaID = $row['facturaID'];
                $clienteName = $row['clienteName'];
                echo "<tr>";
                echo "<td>$facturaID</td>";
                echo "<td>$clienteName</td>";
                echo "<td><form method='POST' action='index.php'><input type='hidden' name='removeFactura' value='$facturaID'><input type='submit' value='Eliminar' class='btn btn-danger'></form>";
                echo "<form method='POST' action='index.php'><input type='hidden' name='openFactura' value='$facturaID'><input type='submit' value='Abrir' class='btn btn-info'></form></td>";
                echo "</tr>";
            }
        } 
    }

   


  
    function getProductById($producto_id){
        global $jsonInfoPath;
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            foreach($json['productos'] as $row) {
                if($row['producto_id'] == $producto_id){
                    return $row;
                }
            }
            return array();
        }
    }

    function getLastFacturaIdInserted(){
        global $jsonInfoPath;
        $last_id = 0;
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(empty($json['facturas'])){
            return $last_id;
        }
        else{
            foreach($json['facturas'] as $row){
                $last_id = (int)$row['facturaID'];
            }
            return $last_id;
        }
    }

    function getProductsByFacturaId($facturaID){
        global $jsonInfoPath;
        $products = array();
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            foreach($json['productos'] as $row) {
                if($row['facturaID'] == $facturaID){
                    array_push($products,$row);
                }
            }
            return $products;
        }
    }

    


    function fillFacturaSection(){
        if(FacturaOpened()){
            if(isset($_POST['openFactura'])){
                $facturaID = $_POST['openFactura'];
            }
            else if(isSaveProductButtonTriggered()){
                $facturaID = $_POST['facturaID'];
                insertNewProduct();
            }
            
            $result = getFacturaById($facturaID);
            $facturaID = $result['facturaID'];
            $clienteName = $result['clienteName'];
            $date = $result['date'];
            $tax = $result['tax'];
            $total = $result['total'];
            echo "<form method='POST' action='index.php'>";
            echo "<div class='form-group'>";
            echo "<label for='facturaID'>ID Factura</label>";
            echo "<input type='text' id='facturaID' name='facturaID' value='$facturaID' class='form-control' disabled>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='date'>Fecha</label>";
            echo "<input type='datetime-local' id='date' name='date' value='$date' class='form-control'>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='clienteName'>Nombre Cliente</label>";
            echo "<input type='text' id='clienteName' name='clienteName' value='$clienteName' class='form-control'>";
            echo "</div>";
            echo "<div class='table-responsive'>";
            echo "<table class='table'><thead class='thead-dark'><tr>";
            echo "<th scope='col'>Cantidad</th><th scope='col'>Descripcion</th><th scope='col'>Valor Unitario</th><th scope='col'>Subtotal</th><th scope='col'>Action</th>";
            echo "</tr></thead>";
            echo "<tbody>";
            $productsresult = getProductsByFacturaId($facturaID);
            if(!empty($productsresult)){
                foreach($productsresult as $row){
                    $producto_id = $row['producto_id'];
                    $cantidad = $row['cantidad'];
                    $descripcion = $row['descripcion'];
                    $valorUnitario = $row['valorUnitario'];
                    $subtotal = $row['subtotal'];
                    echo "<input type='hidden' name='producto_id' value='$producto_id'>";
                    echo "<tr><td>$cantidad</td><td>$descripcion</td><td>$valorUnitario</td><td>$subtotal</td>";
                    echo "<td><input type='submit' name='remover-producto' value='Remover' class='btn btn-danger'></td></tr>";
                }
            }
            echo "<tr>";
            echo "<input type='hidden' name='facturaID' value='$facturaID'>";
            echo "<td><input type='number' id='cantidad' name='cantidad'></td>";
            echo "<td><input type='text' id='descripcion' name='descripcion'></td>";
            echo "<td><input type='number' id='valorUnitario' name='valorUnitario' step='0.01'></td>";
            echo "<td><input type='number' id='subtotal' name='subtotal' step='0.01' disabled></td>";
            echo "<td><input type='submit' name='saveProduct' value='Guardar Producto' class='btn btn-primary'></td>";
            echo "</tr>";
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
            echo "<div class='form-row'>";
            echo "<div class='form-group col-md-6'>";
            echo "<label for='tax'> Impuesto </label>";
            echo "<input type='number' id='tax' name='tax' step='0.01' value='$tax' class='form-control' disabled>";
            echo "</div>";
            echo "<div class='form-group col-md-6'>";
            echo "<label for='total'>Total</label>";
            echo "<input type='number' id='total' name='total' step='0.01' value='$total' class='form-control' disabled>";
            echo "</div>";
            echo "<input type='submit' name='saveFactura' value='Guardar' class='btn btn-primary'>";
            echo "</div>";
            echo "</form>";
        }
        else{
            echo "<form method='POST' action='index.php'>";
            echo "<div class='form-group'>";
            echo "<label for='facturaID'>ID Factura</label>";
            echo "<input type='text' id='facturaID' name='facturaID' class='form-control' disabled>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='date'>Fecha</label>";
            echo "<input type='datetime-local' id='date' name='date' class='form-control'>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='clienteName'>Nombre Cliente</label>";
            echo "<input type='text' id='clienteName' name='clienteName' class='form-control'>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='tax'>Impuesto</label>";
            echo "<input type='number' id='tax' name='tax' step='0.01' value='0.00' class='form-control' disabled>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='total'>Total</label>";
            echo "<input type='number' id='total' name='total' step='0.01' value='0.00' class='form-control' disabled>";
            echo "</div>";
            echo "<input type='submit' name='saveFactura' value='Guardar' class='btn btn-primary'>";
            echo "</form>";
        }
        
    }


    function deleteProductoById($producto_id){
        global $jsonInfoPath;
        
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            $i=0;
            foreach($json['productos'] as $row) {
                if($row['producto_id'] == $producto_id){
                    $subtotal = $row['subtotal'] * -1;
                    $facturaID = $row['facturaID'];
                    unset($json['productos'][$i]);
                }
                $i++;
            }
        }
        $json['productos'] = array_values($json['productos']);
        $dataUpdated = json_encode($json);
        //Save the changes into the file
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
        updateFactura($facturaID,$subtotal);
    }

    function deleteFacturaById($facturaID){
        global $jsonInfoPath;
        deleteAllProductsByFacturaId($facturaID);
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['facturas'])){
            $i=0;
            foreach($json['facturas'] as $row) {
                if($row['facturaID'] == $facturaID){
                    unset($json['facturas'][$i]);
                }
                $i++;
            }
        }
        $json['facturas'] = array_values($json['facturas']);
        $dataUpdated = json_encode($json);
        //Save the changes into the file
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }

?>
<!DOCTYPE html>
<html>
    <head>   
        <meta charset="utf-8">
        <title>Tarea 5</title>
        <link rel="stylesheet" href="styles/style.css">
        <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    </head>
    <body>
        <header></header>
        <main class="container">
            <div class="row">
                <section class="col-md-4" id="receipts">
                    <p>Facturas</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    fillAllReceiptsTable();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <section class="col-md-8" id="factura">
                <p>Factura</p>
                    <?php 
                        fillFacturaSection();
                    ?>
                </section>
            </div>
        </main>
        <footer></footer>
    </body>
</html>