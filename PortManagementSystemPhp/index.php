<?php
// بداية buffer للمخرجات لمنع مشاكل header
ob_start();

require_once 'config.php';

// ========== Form Processing ==========

// Add Ship
if (isset($_POST['add_ship'])) {
    $sql = "EXEC sp_AddShip ?, ?, ?";
    $params = array($_POST['ship_imo'], $_POST['shipname'], $_POST['company']);
    if (query($sql, $params)) {
        setMsg('Ship added successfully!', 'success');
    } else {
        setMsg('Error adding ship!', 'error');
    }
    // إعادة التوجيه باستخدام JavaScript بدلاً من header
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

// Register Arrival
if (isset($_POST['add_arrival'])) {
    $sql = "EXEC sp_RegisterArrival ?, ?, ?";
    $params = array($_POST['arrival_ref'], $_POST['ship_imo'], $_POST['arrival_date']);
    if (query($sql, $params)) {
        setMsg('Arrival registered successfully!', 'success');
    } else {
        setMsg('Error registering arrival!', 'error');
    }
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

// Register Departure
if (isset($_POST['add_departure'])) {
    $sql = "EXEC sp_RegisterDeparture ?, ?";
    $params = array($_POST['arrival_ref'], $_POST['departure_date']);
    if (query($sql, $params)) {
        setMsg('Departure registered successfully!', 'success');
    } else {
        setMsg('Error registering departure!', 'error');
    }
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

// Add Berth
if (isset($_POST['add_berth'])) {
    $sql = "INSERT INTO BERTHS (BERTH_CODE, BERTHNAME, STATUS) VALUES (?, ?, ?)";
    $params = array($_POST['berth_code'], $_POST['berth_name'], $_POST['status']);
    if (query($sql, $params)) {
        setMsg('Berth added successfully!', 'success');
    } else {
        setMsg('Error adding berth!', 'error');
    }
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

// Allocate Berth
if (isset($_POST['allocate_berth'])) {
    $sql = "EXEC sp_AllocateBerth ?, ?, ?";
    $params = array($_POST['alloc_code'], $_POST['arrival_ref'], $_POST['berth_code']);
    if (query($sql, $params)) {
        setMsg('Berth allocated successfully!', 'success');
    } else {
        setMsg('Error allocating berth!', 'error');
    }
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

// Add Container
if (isset($_POST['add_container'])) {
    $sql = "EXEC sp_AddContainer ?, ?, ?, ?";
    $params = array($_POST['container_no'], $_POST['arrival_ref'], $_POST['type'], $_POST['status']);
    if (query($sql, $params)) {
        setMsg('Container added successfully!', 'success');
    } else {
        setMsg('Error adding container!', 'error');
    }
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

// Record Movement
if (isset($_POST['add_movement'])) {
    $sql = "INSERT INTO CONTAINER_MOVEMENTS (CONTAINER_NO, MOVEMENTTYPE, LOCATION) VALUES (?, ?, ?)";
    $params = array($_POST['container_no'], $_POST['movement_type'], $_POST['location']);
    if (query($sql, $params)) {
        setMsg('Movement recorded successfully!', 'success');
    } else {
        setMsg('Error recording movement!', 'error');
    }
    echo '<script>window.location.href = "index.php";</script>';
    exit;
}

// ========== Data Fetching ==========

// Statistics
$stmt = query("SELECT COUNT(*) as c FROM ARRIVALS WHERE DEPARTUREDATE IS NULL");
$current_ships = fetchOne($stmt)['c'];

$stmt = query("SELECT dbo.fn_AvailableBerths() as c");
$result = fetchOne($stmt);
$available_berths = $result['c'] ?? 0;


$stmt = query("SELECT COUNT(*) as c FROM CONTAINERS WHERE STATUS IN ('In Port', 'In Storage')");
$active_containers = fetchOne($stmt)['c'];

$stmt = query("SELECT COUNT(*) as c FROM ARRIVALS WHERE CAST(ARRIVALDATE AS DATE) = CAST(GETDATE() AS DATE)");
$today_arrivals = fetchOne($stmt)['c'];

// Ships
$ships = fetchAll(query("SELECT * FROM SHIPS ORDER BY SHIPNAME"));

// Arrivals with ship info
$arrivals_data = array();
$arrivals = fetchAll(query("SELECT * FROM ARRIVALS ORDER BY ARRIVALDATE DESC"));
foreach ($arrivals as $arrival) {
    $ship = fetchOne(query("SELECT * FROM SHIPS WHERE SHIP_IMO = ?", array($arrival['SHIP_IMO'])));
   $daysRow = fetchOne(query(
    "SELECT dbo.fn_GetShipDays(?) AS d",
    array($arrival['ARRIVAL_REF'])
));
$days = $daysRow['d'] ?? 0;

$containersRow = fetchOne(query(
    "SELECT dbo.fn_CountContainers(?) AS c",
    array($arrival['ARRIVAL_REF'])
));
$containers = $containersRow['c'] ?? 0;
    
    $arrivals_data[] = array_merge($arrival, array(
        'SHIPNAME' => $ship['SHIPNAME'],
        'COMPANY' => $ship['COMPANY'],
        'DAYS' => $days,
        'CONTAINERS' => $containers,
        'ARRIVALDATE_FORMATTED' => $arrival['ARRIVALDATE']->format('Y-m-d')
    ));
}

// Berths
$berths = fetchAll(query("SELECT * FROM BERTHS ORDER BY BERTH_CODE"));

// Allocations
$allocations = fetchAll(query("SELECT * FROM BERTH_ALLOCATIONS"));
$allocations_data = array();
foreach ($allocations as $alloc) {
    $arrival = fetchOne(query("SELECT * FROM ARRIVALS WHERE ARRIVAL_REF = ?", array($alloc['ARRIVAL_REF'])));
    $ship = fetchOne(query("SELECT * FROM SHIPS WHERE SHIP_IMO = ?", array($arrival['SHIP_IMO'])));
    $berth = fetchOne(query("SELECT * FROM BERTHS WHERE BERTH_CODE = ?", array($alloc['BERTH_CODE'])));
    
    $allocations_data[] = array_merge($alloc, array(
        'SHIPNAME' => $ship['SHIPNAME'],
        'BERTHNAME' => $berth['BERTHNAME'],
        'ARRIVALDATE' => $arrival['ARRIVALDATE']
    ));
}

// Containers
$containers = fetchAll(query("SELECT * FROM CONTAINERS ORDER BY CONTAINER_NO"));
$containers_data = array();
foreach ($containers as $container) {
    $last_loc = fetchOne(query("SELECT TOP 1 LOCATION FROM CONTAINER_MOVEMENTS WHERE CONTAINER_NO = ? ORDER BY MOVEMENT_TIME DESC", array($container['CONTAINER_NO'])));
    $movements_count = fetchOne(query("SELECT COUNT(*) as c FROM CONTAINER_MOVEMENTS WHERE CONTAINER_NO = ?", array($container['CONTAINER_NO'])))['c'];
    
    $containers_data[] = array_merge($container, array(
        'LAST_LOCATION' => $last_loc ? $last_loc['LOCATION'] : 'Not specified',
        'MOVEMENTS' => $movements_count
    ));
}

// Ships Report (Cursor)
$ships_report = fetchAll(query("EXEC sp_ShipsReport"));

// Container Movements
$all_movements = fetchAll(query("SELECT c.CONTAINER_NO, cm.MOVEMENT_TIME, cm.MOVEMENTTYPE, cm.LOCATION 
                                  FROM CONTAINERS c, CONTAINER_MOVEMENTS cm 
                                  WHERE c.CONTAINER_NO = cm.CONTAINER_NO 
                                  ORDER BY cm.MOVEMENT_TIME DESC"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Port Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container">
    
    <!-- Header -->
    <div class="header">
        <h1><i class="fas fa-ship"></i> Port Management System</h1>
        <p style="color: #666;">Port Management System</p>
        
        <div class="nav">
            <button onclick="showPage('dashboard')" class="active">
                <i class="fas fa-home"></i> Dashboard
            </button>
            <button onclick="showPage('ships')">
                <i class="fas fa-ship"></i> Ships
            </button>
            <button onclick="showPage('arrivals')">
                <i class="fas fa-anchor"></i> Arrivals
            </button>
            <button onclick="showPage('berths')">
                <i class="fas fa-warehouse"></i> Berths
            </button>
            <button onclick="showPage('containers')">
                <i class="fas fa-boxes"></i> Containers
            </button>
            <button onclick="showPage('reports')">
                <i class="fas fa-chart-bar"></i> Reports
            </button>
        </div>
    </div>

    <?php showMsg(); ?>

    <!-- ==================== Dashboard ==================== -->
    <div id="dashboard" class="page active">
        
        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-ship"></i>
                <h3><?php echo $current_ships; ?></h3>
                <p>Ships in Port</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-warehouse"></i>
                <h3><?php echo $available_berths; ?></h3>
                <p>Available Berths</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-boxes"></i>
                <h3><?php echo $active_containers; ?></h3>
                <p>Active Containers</p>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-anchor"></i>
                <h3><?php echo $today_arrivals; ?></h3>
                <p>Today's Arrivals</p>
            </div>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-history"></i> Recent Arrivals</h2>
            <?php if (count($arrivals_data) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Arrival Ref.</th>
                        <th>Ship</th>
                        <th>Company</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($arrivals_data, 0, 5) as $arr): ?>
                    <tr>
                        <td><?php echo $arr['ARRIVAL_REF']; ?></td>
                        <td><?php echo $arr['SHIPNAME']; ?></td>
                        <td><?php echo $arr['COMPANY']; ?></td>
                        <td><?php echo $arr['ARRIVALDATE_FORMATTED']; ?></td>
                        <td>
                            <?php if ($arr['DEPARTUREDATE']): ?>
                                <span class="badge badge-warning">Departed</span>
                            <?php else: ?>
                                <span class="badge badge-success">In Port</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty">
                <i class="fas fa-inbox"></i>
                <h3>No arrivals found</h3>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ==================== Ships ==================== -->
    <div id="ships" class="page">
        
        <div class="content-box">
            <h2><i class="fas fa-plus-circle"></i> Add New Ship</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>IMO Number</label>
                        <input type="text" name="ship_imo" placeholder="IMO-1234567" required>
                    </div>
                    <div class="form-group">
                        <label>Ship Name</label>
                        <input type="text" name="shipname" placeholder="Ship Name" required>
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company" placeholder="Company Name" required>
                    </div>
                </div>
                <button type="submit" name="add_ship" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add
                </button>
            </form>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-list"></i> Ships List</h2>
            <div class="search-box">
                <input type="text" id="searchShips" placeholder="🔍 Search ship..." onkeyup="searchTable('searchShips', 'shipsTable')">
            </div>
            <button onclick="exportCSV('shipsTable', 'ships')" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
            <button onclick="printTable('shipsTable', 'Ships List')" class="btn btn-info">
                <i class="fas fa-print"></i> Print
            </button>
            
            <?php if (count($ships) > 0): ?>
            <table class="data-table" id="shipsTable">
                <thead>
                    <tr>
                        <th onclick="sortTable('shipsTable', 0)" style="cursor:pointer;">IMO Number <i class="fas fa-sort"></i></th>
                        <th onclick="sortTable('shipsTable', 1)" style="cursor:pointer;">Ship Name <i class="fas fa-sort"></i></th>
                        <th onclick="sortTable('shipsTable', 2)" style="cursor:pointer;">Company <i class="fas fa-sort"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ships as $ship): ?>
                    <tr>
                        <td><?php echo $ship['SHIP_IMO']; ?></td>
                        <td><?php echo $ship['SHIPNAME']; ?></td>
                        <td><?php echo $ship['COMPANY']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty">
                <i class="fas fa-ship"></i>
                <h3>No ships found</h3>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ==================== Arrivals ==================== -->
    <div id="arrivals" class="page">
        
        <div class="content-box">
            <h2><i class="fas fa-plus-circle"></i> Register New Arrival</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Arrival Reference</label>
                        <input type="text" name="arrival_ref" id="arrivalRef" required>
                        <button type="button" onclick="autoFillRef('arrivalRef', 'ARR')" class="btn btn-info" style="margin-top:5px; padding:8px 15px; font-size:14px;">
                            <i class="fas fa-magic"></i> Auto Generate
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Select Ship</label>
                        <select name="ship_imo" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($ships as $ship): ?>
                            <option value="<?php echo $ship['SHIP_IMO']; ?>"><?php echo $ship['SHIPNAME']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Arrival Date</label>
                        <input type="date" name="arrival_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <button type="submit" name="add_arrival" class="btn btn-primary">
                    <i class="fas fa-anchor"></i> Register
                </button>
            </form>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-ship"></i> Register Departure</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Arrival Reference</label>
                        <select name="arrival_ref" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($arrivals_data as $arr): ?>
                            <?php if (!$arr['DEPARTUREDATE']): ?>
                            <option value="<?php echo $arr['ARRIVAL_REF']; ?>">
                                <?php echo $arr['ARRIVAL_REF'] . ' - ' . $arr['SHIPNAME']; ?>
                            </option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Departure Date</label>
                        <input type="date" name="departure_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <button type="submit" name="add_departure" class="btn btn-success">
                    <i class="fas fa-check"></i> Register Departure
                </button>
            </form>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-list"></i> Arrivals List</h2>
            <div class="search-box">
                <input type="text" id="searchArrivals" placeholder="🔍 Search..." onkeyup="searchTable('searchArrivals', 'arrivalsTable')">
            </div>
            
            <?php if (count($arrivals_data) > 0): ?>
            <table class="data-table" id="arrivalsTable">
                <thead>
                    <tr>
                        <th>Arrival Ref.</th>
                        <th>Ship</th>
                        <th>Company</th>
                        <th>Arrival</th>
                        <th>Departure</th>
                        <th>Duration</th>
                        <th>Containers</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($arrivals_data as $arr): ?>
                    <tr>
                        <td><?php echo $arr['ARRIVAL_REF']; ?></td>
                        <td><?php echo $arr['SHIPNAME']; ?></td>
                        <td><?php echo $arr['COMPANY']; ?></td>
                        <td><?php echo $arr['ARRIVALDATE_FORMATTED']; ?></td>
                        <td><?php echo $arr['DEPARTUREDATE'] ? date('Y-m-d', strtotime($arr['DEPARTUREDATE'])) : '-'; ?></td>
                        <td><?php echo $arr['DAYS']; ?> days</td>
                        <td><?php echo $arr['CONTAINERS']; ?></td>
                        <td>
                            <?php if ($arr['DEPARTUREDATE']): ?>
                                <span class="badge badge-warning">Departed</span>
                            <?php else: ?>
                                <span class="badge badge-success">In Port</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty">
                <i class="fas fa-anchor"></i>
                <h3>No arrivals found</h3>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ==================== Berths ==================== -->
    <div id="berths" class="page">
        
        <div class="content-box">
            <h2><i class="fas fa-plus-circle"></i> Add New Berth</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Berth Code</label>
                        <input type="text" name="berth_code" placeholder="B-01" required>
                    </div>
                    <div class="form-group">
                        <label>Berth Name</label>
                        <input type="text" name="berth_name" placeholder="Berth 1" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="Available">Available</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_berth" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add
                </button>
            </form>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-link"></i> Allocate Berth to Ship</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Allocation Code</label>
                        <input type="text" name="alloc_code" id="allocCode" required>
                        <button type="button" onclick="autoFillRef('allocCode', 'ALLOC')" class="btn btn-info" style="margin-top:5px; padding:8px 15px; font-size:14px;">
                            <i class="fas fa-magic"></i> Generate
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Arrival</label>
                        <select name="arrival_ref" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($arrivals_data as $arr): ?>
                            <?php if (!$arr['DEPARTUREDATE']): ?>
                            <option value="<?php echo $arr['ARRIVAL_REF']; ?>">
                                <?php echo $arr['ARRIVAL_REF'] . ' - ' . $arr['SHIPNAME']; ?>
                            </option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Berth</label>
                        <select name="berth_code" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($berths as $berth): ?>
                            <?php if ($berth['STATUS'] == 'Available'): ?>
                            <option value="<?php echo $berth['BERTH_CODE']; ?>">
                                <?php echo $berth['BERTHNAME']; ?>
                            </option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="allocate_berth" class="btn btn-success">
                    <i class="fas fa-link"></i> Allocate
                </button>
            </form>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-warehouse"></i> Berth Status</h2>
            <div class="stats">
                <?php foreach ($berths as $berth): ?>
                <div class="stat-card">
                    <i class="fas fa-parking"></i>
                    <p style="font-size:18px; font-weight:600; margin:10px 0;">
                        <?php echo $berth['BERTHNAME']; ?>
                    </p>
                    <span class="badge <?php 
                        echo ($berth['STATUS'] == 'Available') ? 'badge-success' : 
                             (($berth['STATUS'] == 'Reserved') ? 'badge-danger' : 'badge-warning'); 
                    ?>">
                        <?php echo $berth['STATUS']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-list"></i> Current Allocations</h2>
            <?php if (count($allocations_data) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Allocation Code</th>
                        <th>Arrival Ref.</th>
                        <th>Ship</th>
                        <th>Berth</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allocations_data as $alloc): ?>
                    <tr>
                        <td><?php echo $alloc['ALLOC_CODE']; ?></td>
                        <td><?php echo $alloc['ARRIVAL_REF']; ?></td>
                        <td><?php echo $alloc['SHIPNAME']; ?></td>
                        <td><?php echo $alloc['BERTHNAME']; ?></td>
                        <td><?php echo date('Y-m-d', strtotime($alloc['ARRIVALDATE'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty">
                <i class="fas fa-link"></i>
                <h3>No allocations found</h3>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ==================== Containers ==================== -->
    <div id="containers" class="page">
        
        <div class="content-box">
            <h2><i class="fas fa-plus-circle"></i> Add New Container</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Container Number</label>
                        <input type="text" name="container_no" placeholder="MSCU1234567" required>
                    </div>
                    <div class="form-group">
                        <label>Arrival</label>
                        <select name="arrival_ref" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($arrivals_data as $arr): ?>
                            <option value="<?php echo $arr['ARRIVAL_REF']; ?>">
                                <?php echo $arr['ARRIVAL_REF'] . ' - ' . $arr['SHIPNAME']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" required>
                            <option value="20FT">20FT</option>
                            <option value="40FT">40FT</option>
                            <option value="Refrigerated">Refrigerated</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="In Port">In Port</option>
                            <option value="In Storage">In Storage</option>
                            <option value="Loaded">Loaded</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_container" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add
                </button>
            </form>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-route"></i> Record Container Movement</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Container Number</label>
                        <select name="container_no" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($containers as $c): ?>
                            <option value="<?php echo $c['CONTAINER_NO']; ?>"><?php echo $c['CONTAINER_NO']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Movement Type</label>
                        <select name="movement_type" required>
                            <option value="Unloading">Unloading</option>
                            <option value="Loading">Loading</option>
                            <option value="Transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="Location" required>
                    </div>
                </div>
                <button type="submit" name="add_movement" class="btn btn-success">
                    <i class="fas fa-check"></i> Record
                </button>
            </form>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-list"></i> Containers List</h2>
            <div class="search-box">
                <input type="text" id="searchContainers" placeholder="🔍 Search..." onkeyup="searchTable('searchContainers', 'containersTable')">
            </div>
            
            <?php if (count($containers_data) > 0): ?>
            <table class="data-table" id="containersTable">
                <thead>
                    <tr>
                        <th>Container No.</th>
                        <th>Arrival Ref.</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Last Location</th>
                        <th>Movements</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($containers_data as $cont): ?>
                    <tr>
                        <td><?php echo $cont['CONTAINER_NO']; ?></td>
                        <td><?php echo $cont['ARRIVAL_REF']; ?></td>
                        <td><?php echo $cont['TYPE']; ?></td>
                        <td>
                            <span class="badge <?php 
                                echo ($cont['STATUS'] == 'In Port') ? 'badge-info' : 
                                     (($cont['STATUS'] == 'In Storage') ? 'badge-success' : 'badge-warning'); 
                            ?>">
                                <?php echo $cont['STATUS']; ?>
                            </span>
                        </td>
                        <td><?php echo $cont['LAST_LOCATION']; ?></td>
                        <td><?php echo $cont['MOVEMENTS']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty">
                <i class="fas fa-boxes"></i>
                <h3>No containers found</h3>
            </div>
            <?php endif; ?>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-history"></i> Movements History</h2>
            <?php if (count($all_movements) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Container No.</th>
                        <th>Date & Time</th>
                        <th>Movement Type</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($all_movements, 0, 20) as $mov): ?>
                    <tr>
                        <td><?php echo $mov['CONTAINER_NO']; ?></td>
                        <td>
                            <?php 
                            $dt = $mov['MOVEMENT_TIME'];
                            echo ($dt instanceof DateTime) ? $dt->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($dt));
                            ?>
                        </td>
                        <td><span class="badge badge-info"><?php echo $mov['MOVEMENTTYPE']; ?></span></td>
                        <td><?php echo $mov['LOCATION']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty">
                <i class="fas fa-history"></i>
                <h3>No movements found</h3>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ==================== Reports ==================== -->
    <div id="reports" class="page">
        
        <div class="content-box">
            <h2><i class="fas fa-ship"></i> Ships in Port Report (Cursor Procedure)</h2>
            <p style="background:#f8f9ff; padding:15px; border-radius:10px; margin:15px 0;">
                <i class="fas fa-info-circle" style="color:#667eea;"></i>
                <strong>Note:</strong> This report uses Cursor Procedure (sp_ShipsReport) for data processing
            </p>
            
            <?php if (count($ships_report) > 0): ?>
            <button onclick="exportCSV('reportTable', 'ships_report')" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
            <button onclick="printTable('reportTable', 'Ships Report')" class="btn btn-info">
                <i class="fas fa-print"></i> Print
            </button>
            
            <table class="data-table" id="reportTable">
                <thead>
                    <tr>
                        <th>IMO Number</th>
                        <th>Ship Name</th>
                        <th>Company</th>
                        <th>Arrival Ref.</th>
                        <th>Arrival Date</th>
                        <th>Duration (Days)</th>
                        <th>Containers Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ships_report as $rep): ?>
                    <tr>
                        <td><?php echo $rep['SHIP_IMO']; ?></td>
                        <td><?php echo $rep['SHIPNAME']; ?></td>
                        <td><?php echo $rep['COMPANY']; ?></td>
                        <td><?php echo $rep['ARRIVAL_REF']; ?></td>
                        <td><?php echo date('Y-m-d', strtotime($rep['ARRIVALDATE'])); ?></td>
                        <td><?php echo $rep['DAYS']; ?> days</td>
                        <td><?php echo $rep['CONTAINERS']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty">
                <i class="fas fa-ship"></i>
                <h3>No ships currently in port</h3>
            </div>
            <?php endif; ?>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-function"></i> SQL Functions Used</h2>
            <div class="stats">
                <div class="stat-card">
                    <i class="fas fa-calculator"></i>
                    <h3>fn_GetShipDays</h3>
                    <p>Calculate ship stay duration</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-hashtag"></i>
                    <h3>fn_CountContainers</h3>
                    <p>Count containers for arrival</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-warehouse"></i>
                    <h3>fn_AvailableBerths</h3>
                    <p>Count available berths</p>
                </div>
            </div>
        </div>

        <div class="content-box">
                        <h2><i class="fas fa-bolt"></i> Stored Procedures Used</h2>
            <div style="background:#f8f9ff; padding:20px; border-radius:10px;">
                <ul style="list-style:none; padding:0;">
                    <li style="padding:10px; border-bottom:1px solid #e0e0e0;">
                        <i class="fas fa-check" style="color:#43e97b;"></i> <strong>sp_AddShip</strong> - Add ship
                    </li>
                    <li style="padding:10px; border-bottom:1px solid #e0e0e0;">
                        <i class="fas fa-check" style="color:#43e97b;"></i> <strong>sp_RegisterArrival</strong> - Register arrival
                    </li>
                    <li style="padding:10px; border-bottom:1px solid #e0e0e0;">
                        <i class="fas fa-check" style="color:#43e97b;"></i> <strong>sp_AllocateBerth</strong> - Allocate berth
                    </li>
                    <li style="padding:10px; border-bottom:1px solid #e0e0e0;">
                        <i class="fas fa-check" style="color:#43e97b;"></i> <strong>sp_AddContainer</strong> - Add container
                    </li>
                    <li style="padding:10px; border-bottom:1px solid #e0e0e0;">
                        <i class="fas fa-check" style="color:#43e97b;"></i> <strong>sp_RegisterDeparture</strong> - Register departure
                    </li>
                    <li style="padding:10px;">
                        <i class="fas fa-check" style="color:#43e97b;"></i> <strong>sp_ShipsReport</strong> - Ships report (Cursor)
                    </li>
                </ul>
            </div>
        </div>

        <div class="content-box">
            <h2><i class="fas fa-shield-alt"></i> Active Triggers</h2>
            <div style="background:#f8f9ff; padding:20px; border-radius:10px;">
                <ul style="list-style:none; padding:0;">
                    <li style="padding:10px; border-bottom:1px solid #e0e0e0;">
                        <i class="fas fa-bolt" style="color:#fa709a;"></i> <strong>trg_BerthAlloc</strong> - Automatically update berth status on allocation
                    </li>
                    <li style="padding:10px;">
                        <i class="fas fa-bolt" style="color:#fa709a;"></i> <strong>trg_ContainerAdd</strong> - Automatically record movement when adding container
                    </li>
                </ul>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <div class="header" style="margin-top:40px;">
        <p>&copy; 2024 Port Management System - University Project</p>
        <p style="font-size:14px; color:#999; margin-top:10px;">
            Contains: Stored Procedures | Functions | Triggers | Cursors | SQL Queries
        </p>
    </div>

</div>

<script src="script.js"></script>
</body>
</html>
<?php
// إرسال buffer للمخرجات
ob_end_flush();
?>