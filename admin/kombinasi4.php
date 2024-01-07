<!DOCTYPE html>
<?php
$menu5 = "active";
error_reporting(0);
include "../config/koneksi.php";
$bulan = $_POST['bulan'];
$minggu = $_POST['minggu'];
$tahun = $_POST['tahun'];

$r_minsup = 0;

$lengthData = 0;

if (isset($_POST['kirim_minsup'])) {
    $minsup = $_POST['minsup'];
    $minconf = $_POST['minconf'];

    $q_minsup = "UPDATE tbl_minsup SET minsup = $minsup, minconf = $minconf";

    $r_minsup = $conn->query($q_minsup);

    // die(print_r($r_minsup));
}

$s_minsup = "SELECT * FROM tbl_minsup";
$ss_minsup = $conn->query($s_minsup);
// die(print_r($ss_minsup));
foreach ($ss_minsup as $sss) {
    $minimsl_sup = $sss['minsup'];
    $minimsl_conf = $sss['minconf'];
}

$jumlahTransaksi = 0;

// if ($bulan != null ) include "eclat-table.php";

?>
<html>

<head>
    <title>Kombinasi Asosiasi</title>
    <?php include "head.php"; ?>
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        select {
            padding: 7px;
        }

        @media (max-width: 768px) {
            select {
                width: 100%;
                box-sizing: border-box;
                /* Ini akan membuat padding dan border menjadi bagian dari lebar elemen, sehingga mencegah elemen melebihi 100% lebar */
            }

            button {
                margin-top: 12px;
            }
        }
    </style>
</head>

<body>

    <?php include "sidebar.php"; ?>

    <div class="content pb-3">
        <div class="container pt-3">
            <h3>Kombinasi Aturan Asosiasi
                <br>

            </h3>
            Kombinasi, Support A, Support A,B, Confidence (A,B)
            <hr>

            <font style="font-family: 'Lucida Sans';">Jumlah Transaksi Sebanyak <?= $lengthData ?> dengan Jumlah Item Sebanyak 828 </font>

            <br>
            <br>
            <form action="" method="POST">
                <label for="minsup">Masukkan Minimal Support (%):</label>
                <input type="text" name="minsup" id="minsup" value="<?= $minimsl_sup ?>" style="padding: 5px; width: 50px;">

                <label for="minconf">Masukkan Minimal Confidance (%):</label>
                <input type="text" name="minconf" id="minconf" value="<?= $minimsl_conf ?>" style="padding: 5px; width: 50px;">
                <button type="submit" name="kirim_minsup" style="color: #fff; background-color: #007bff; border-color: #007bff; padding: 7px; border: none;">Lihat
                    Kirim</button>
            </form>
            <br>

            <form action="" method="POST">
                <label for="month">Pilih Bulan:</label>
                <select name="bulan" id="bulan" style="width: 120px;">
                    <?php
                    $months = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
                    foreach ($months as $key => $value) {
                        echo "<option value=\"$key\"";
                        if (isset($_POST['bulan']) && $_POST['bulan'] == $key) {
                            echo ' selected="selected"';
                        }
                        echo ">$value</option>";
                    }
                    ?>
                </select>

                <label for="tahun">Pilih Tahun:</label>
                <select name="tahun" id="tahun" style="width: 120px;">
                    <?php
                    $years = array('2021');
                    foreach ($years as $year) {
                        echo "<option";
                        if (isset($_POST['tahun']) && $_POST['tahun'] == $year) {
                            echo ' selected="selected"';
                        }
                        echo ">$year</option>";
                    }
                    ?>
                </select>
                <button type="submit" style="color: #fff; background-color: #007bff; border-color: #007bff; padding: 7px; border: none;">Lihat
                    Hasil</button>
            </form>

            <div class="mt-5">
                <div class="table-responsive">
                    <table id="example" class="table table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Kombinasi Item</th>
                                <th>Dukungan Support (A)</th>
                                <th>Dukungan Support (A,B)</th>
                                <th>Confidence (A,B)</th>
                            </tr>
                        </thead>

                        <?php

                        function logO($t, $m)
                        {
                            echo '<pre>';
                            print_r($t . ": " . json_encode($m, JSON_PRETTY_PRINT));
                            echo '</pre>';
                        }

                        function calculateTIDsets($transactions)
                        {
                            $tidsets = [];

                            foreach ($transactions as $transaction) {
                                $tid = $transaction['Transaksi_Id'];
                                foreach ($transaction as $key => $value) {
                                    if ($key !== 'Transaksi_Id' && $key !== 'No' && $key !== 'tanggal' && $value !== null) {
                                        if (!isset($tidsets[$value])) {
                                            $tidsets[$value] = [];
                                        }
                                        $tidsets[$value][] = $tid;
                                    }
                                }
                            }

                            return $tidsets;
                        }


                        function findAssociation($tidsets, $purchases)
                        {
                            $associatedItems = [];
                            $transactionList = [];

                            foreach ($purchases as $purchase) {
                                $transactionList[] = $tidsets[$purchase];
                            }

                            // Menemukan item yang ada di semua transaksi
                            $commonTransactions = call_user_func_array('array_intersect', $transactionList);

                            // Menghitung jumlah kemunculan item terkait
                            $itemCount = [];

                            foreach ($commonTransactions as $commonTransaction) {
                                foreach ($tidsets as $item => $tidList) {
                                    if (in_array($commonTransaction, $tidList) && !in_array($item, $purchases)) {
                                        if (!isset($itemCount[$item])) {
                                            $itemCount[$item] = 1;
                                        } else {
                                            $itemCount[$item]++;
                                        }
                                    }
                                }
                            }

                            $associatedItems = array_filter($itemCount, function ($key) {
                                return $key !== "";
                            }, ARRAY_FILTER_USE_KEY);

                            // Mengurutkan berdasarkan jumlah kemunculan
                            arsort($associatedItems);

                            return array_keys($associatedItems);
                        }


                        // function findAssociation($tidsets, $purchases, $support)
                        // {
                        //     $associatedItems = [];
                        //     $transactionList = [];

                        //     logO("purchases", $purchases);

                        //     foreach ($purchases as $purchase) {
                        //         $transactionList[] = $tidsets[$purchase];
                        //     }

                        //     foreach($transactionList as $key => $transaction) {
                        //         $sumTransaction = count($transaction);
                        //         logO("sumTransaction", $sumTransaction);

                        //         if ($sumTransaction < $support) {
                        //             unset($transactionList[$key]);
                        //         }
                        //     }

                        //     if (count($transactionList) > 1) {
                        //         for ($i=0; $i < count($transactionList); $i++) { 
                        //             for ()
                        //         }
                        //     }



                        //     logO("transactionList", $transactionList);

                        //     // Menemukan item yang ada di semua transaksi

                        //     $commonTransactions = call_user_func_array('array_intersect', $transactionList);

                        //     logO("commonTransactions", $commonTransactions);

                        //     foreach ($commonTransactions as $commonTransaction) {
                        //         foreach ($tidsets as $item => $tidList) {
                        //             if (in_array($commonTransaction, $tidList) && !in_array($item, $purchases)) {
                        //                 if (!isset($associatedItems[$item])) {
                        //                     $associatedItems[$item] = 0;
                        //                 }
                        //                 $associatedItems[$item]++;
                        //             }
                        //         }
                        //     }

                        //     // // Implementasi algoritma Eclat untuk menemukan itemset yang sering muncul bersama
                        //     // $support = 1; // Atur nilai support threshold di sini
                        //     // $frequentItemsets = [];
                        //     // foreach ($transactionList as $transaction) {
                        //     //     foreach ($transaction as $item) {
                        //     //         if (!isset($frequentItemsets[$item])) {
                        //     //             $frequentItemsets[$item] = 0;
                        //     //         }
                        //     //         $frequentItemsets[$item]++;
                        //     //     }
                        //     // }

                        //     // logO("frequentItemsets", $frequentItemsets);

                        //     // foreach ($frequentItemsets as $item => $count) {
                        //     //     if ($count >= $support && !in_array($item, $purchases)) {
                        //     //         $associatedItems[$item] = $count;
                        //     //     }
                        //     // }

                        //     $associatedItems = array_filter($associatedItems, function ($key) {
                        //         return $key !== "";
                        //     }, ARRAY_FILTER_USE_KEY);

                        //     logO("associatedItems", $associatedItems);

                        //     // Pilih item dengan frekuensi terbanyak
                        //     arsort($associatedItems);
                        //     $topItem = key($associatedItems);

                        //     // logO("topItem", $topItem);

                        //     return [$topItem];
                        // }

                        // function findAssociation($tidsets, $purchases)
                        // {
                        //     $associatedItems = [];
                        //     $transactionList = [];

                        //     foreach ($purchases as $purchase) {
                        //         $transactionList[] = $tidsets[$purchase];
                        //     }

                        //     // Menemukan item yang ada di semua transaksi
                        //     $commonTransactions = call_user_func_array('array_intersect', $transactionList);

                        //     foreach ($commonTransactions as $commonTransaction) {
                        //         foreach ($tidsets as $item => $tidList) {
                        //             if (in_array($commonTransaction, $tidList) && !in_array($item, $purchases)) {
                        //                 $associatedItems[] = $item;
                        //             }
                        //         }
                        //     }

                        //     $uniqueItems = array_unique($associatedItems);

                        //     // Mengembalikan satu item saja
                        //     if (!empty($uniqueItems)) {
                        //         $result = [];
                        //         foreach ($uniqueItems as $item) {
                        //             if (!empty($item)) {
                        //                 $result[] = $item;
                        //                 break; // Menghentikan iterasi setelah menemukan item yang tidak kosong
                        //             }
                        //         }

                        //         return $result;
                        //     }

                        //     return []; // Jika tidak ada item yang terkait
                        // }

                        function calculateSupport($tidsets, $items, $sizeData, $i)
                        {
                            $transactionList = [];

                            foreach ($items as $associatedItem) {
                                $transactionList[] = $tidsets[$associatedItem];
                            }

                            $commonTransactions = call_user_func_array('array_intersect', $transactionList);

                            $support = ((count($commonTransactions)) / $sizeData) * 100;

                            logO("items", $items);
                            logO("transactionList", $transactionList);
                            logO("commonTransactions", $commonTransactions);
                            logO("sizeData", $sizeData);
                            logO("support", $support);
                            logO("=", "===========");

                            return $support;
                        }

                        function calculateSupportAB($tidsets, $items, $associatedItems, $sizeData)
                        {
                            // $sumSupport = 0;

                            // foreach ($items as $i) {
                            //     $getCount = count($tidsets[$i]);
                            //     $sumSupport += $getCount;
                            // }

                            // foreach ($associatedItems as $i) {
                            //     $getCount = count($tidsets[$i]);
                            //     $sumSupport += $getCount;
                            // }

                            // return ($sumSupport / $sizeData) * 100;

                            $transactionList = [];

                            foreach (array_merge($items, $associatedItems) as $associatedItem) {
                                $transactionList[] = $tidsets[$associatedItem];
                            }

                            $commonTransactions = call_user_func_array('array_intersect', $transactionList);


                            return ((count($commonTransactions)) / $sizeData) * 100;
                        }

                        if ($tahun != null) {
                            $data = $conn->query("SELECT * FROM tbl_transaksi WHERE YEAR(tanggal) = $tahun AND MONTH(tanggal) = $bulan ORDER BY tanggal");
                            // $data = $conn->query("SELECT * FROM tbl_transaksi");

                            $lengthData = $data->num_rows;

                            $tidsets = calculateTIDsets($data);

                            $output = [];

                            $i = 0;


                            foreach ($data as $d) {
                                $itemsToAnalyze = [
                                    $d["Item_1"], $d["Item_2"], $d["Item_3"],
                                    $d["Item_4"], $d["Item_5"],
                                    $d["Item_6"], $d["Item_7"],
                                    $d["Item_8"], $d["Item_9"], $d["Item_10"], $d["Item_11"], $d["Item_12"], $d["Item_13"], $d["Item_14"], $d["Item_15"], $d["Item_16"]
                                ];

                                $itemsToAnalyze = array_filter($itemsToAnalyze);


                                $associatedItems = findAssociation($tidsets, $itemsToAnalyze);

                                $supportA = calculateSupport($tidsets, $itemsToAnalyze, $lengthData, $i);

                                $i++;



                                if ($associatedItems[0] != null) {

                                    $supportAB = calculateSupportAB($tidsets, $itemsToAnalyze, array_slice($associatedItems, 0, 1,), $lengthData);
                                    $confidence = $supportAB / $supportA;

                                    if ($supportA >= $minimsl_sup) {
                                        array_push($output, [
                                            // "output" => "Jika membeli " . implode(' dan ', $itemsToAnalyze) . " maka akan membeli " . implode(', ', $associatedItems),
                                            "output" => "Jika membeli " . implode(' dan ', $itemsToAnalyze) . " maka akan membeli " . $associatedItems[0],
                                            "supportA" => round($supportA, 2),
                                            "supportAB" => round($supportAB, 2),
                                            "confidence" => round($confidence, 2)
                                        ]);
                                    }
                                }
                            }


                            $uniqueData = array_reduce($output, function ($carry, $item) {
                                $output = $item['output'];

                                if (!isset($carry[$output])) {
                                    $carry[$output] = $item;
                                }

                                return $carry;
                            }, []);

                            $uniqueData = array_values($uniqueData);

                            foreach ($uniqueData as $o) {
                                echo "<tr>";
                                // echo "<td>Jika membeli " . implode(' dan ', $itemsToAnalyze) . " maka akan membeli " . implode(', ', $associatedItems) . "\n";
                                echo "<td>" . $o["output"] . "\n";
                                echo "<td>" . $o["supportA"] . "%</td>";
                                echo "<td>" . $o["supportAB"] . "%</td>";
                                echo "<td>" . $o["confidence"] . "%</td>";
                                echo "</tr>";
                            }
                        }



                        // Tambahkan di awal kode untuk menyimpan kombinasi unik

                        // $kombinasi_unik = array();

                        // // Ambil total transaksi
                        // $sqlTotal = "SELECT COUNT(*) AS total FROM tbl_transaksi WHERE MONTH(tanggal) = $bulan 
                        //      AND WEEK(tanggal) = (SELECT WEEK(MIN(tanggal)) FROM tbl_transaksi WHERE MONTH(tanggal) = $bulan) + $minggu";
                        // $resultTotal = $conn->query($sqlTotal);
                        // $rowTotal = $resultTotal->fetch_assoc();
                        // $totalTransaksi = $rowTotal["total"];

                        // $sql = "SELECT Item_1, COUNT(*) as jumlahTransaksiA
                        // FROM tbl_transaksi t
                        // WHERE MONTH(tanggal) = $bulan AND WEEK(tanggal) = (SELECT WEEK(MIN(tanggal)) FROM tbl_transaksi WHERE MONTH(tanggal) = $bulan) + $minggu AND 
                        //       (Item_1 = t.Item_1 OR Item_2 = t.Item_1 OR Item_3 = t.Item_1 OR
                        //        Item_4 = t.Item_1 OR Item_5 = t.Item_1 OR Item_6 = t.Item_1 OR 
                        //        Item_7 = t.Item_1 OR Item_8 = t.Item_1 OR Item_9 = t.Item_1 OR 
                        //        Item_10 = t.Item_1 OR Item_11 = t.Item_1 OR Item_12 = t.Item_1 OR 
                        //        Item_13 = t.Item_1 OR Item_14 = t.Item_1 OR Item_15 = t.Item_1 OR 
                        //        Item_16 = t.Item_1)
                        // GROUP BY Item_1";

                        // $result = $conn->query($sql);
                        // $items = array();

                        // if ($result && $result->num_rows > 0) {
                        //     while ($row = $result->fetch_assoc()) {
                        //         $items[] = array("item" => addslashes($row["Item_1"]), "jumlahTransaksiA" => $row["jumlahTransaksiA"]);
                        //     }
                        // }

                        // // Hitung dukungan dan confidence untuk setiap kombinasi item
                        // foreach ($items as $itemA) {
                        //     foreach ($items as $itemB) {
                        //         if ($itemA["item"] != $itemB["item"]) {

                        //             // Buat kombinasi yang diurutkan agar tidak ada yang duplikat
                        //             $sortedCombination = "{$itemA['item']}|{$itemB['item']}";
                        //             $reverseSortedCombination = "{$itemB['item']}|{$itemA['item']}";

                        //             if (!in_array($sortedCombination, $kombinasi_unik) && !in_array($reverseSortedCombination, $kombinasi_unik)) {
                        //                 $itemAItem = addslashes($itemA['item']);
                        //                 $itemBItem = addslashes($itemB['item']);

                        //                 $sqlAB = "SELECT COUNT(*) AS jumlahAB 
                        //         FROM tbl_transaksi 
                        //         WHERE (Item_1 = '$itemAItem' OR Item_2 = '$itemAItem' OR Item_3 = '$itemAItem' 
                        //                OR Item_4 = '$itemAItem' OR Item_5 = '$itemAItem' OR Item_6 = '$itemAItem' 
                        //                OR Item_7 = '$itemAItem' OR Item_8 = '$itemAItem' OR Item_9 = '$itemAItem' 
                        //                OR Item_10 = '$itemAItem' OR Item_11 = '$itemAItem' OR Item_12 = '$itemAItem' 
                        //                OR Item_13 = '$itemAItem' OR Item_14 = '$itemAItem' OR Item_15 = '$itemAItem' 
                        //                OR Item_16 = '$itemAItem') 
                        //         AND (Item_1 = '$itemBItem' OR Item_2 = '$itemBItem' OR Item_3 = '$itemBItem' 
                        //              OR Item_4 = '$itemBItem' OR Item_5 = '$itemBItem' OR Item_6 = '$itemBItem' 
                        //              OR Item_7 = '$itemBItem' OR Item_8 = '$itemBItem' OR Item_9 = '$itemBItem' 
                        //              OR Item_10 = '$itemBItem' OR Item_11 = '$itemBItem' OR Item_12 = '$itemBItem' 
                        //              OR Item_13 = '$itemBItem' OR Item_14 = '$itemBItem' OR Item_15 = '$itemBItem' 
                        //              OR Item_16 = '$itemBItem')  
                        //         AND MONTH(tanggal) = 4";

                        //                 $resultAB = $conn->query($sqlAB);
                        //                 if (!$resultAB) {
                        //                     die("Error: " . $conn->error);
                        //                 }

                        //                 $rowAB = $resultAB->fetch_assoc();

                        //                 // Hitung dukungan dan confidence
                        //                 $supportA = ($itemA["jumlahTransaksiA"] / $totalTransaksi) * 100;
                        //                 $supportAB = ($rowAB["jumlahAB"] / $totalTransaksi) * 100;
                        //                 $confidenceAB = ($supportAB / $supportA) * 100;

                        //                 // Tampilkan hanya jika confidence lebih dari 0% dan kurang dari atau sama dengan 100%
                        //                 // Tampilkan hanya jika confidence lebih dari atau sama dengan 2% dan supportAB lebih dari atau sama dengan 2%
                        //                 if ($confidenceAB >= $minimsl_conf && $supportA >= $minimsl_sup && $supportAB >= $minimsl_sup) {
                        //                     echo "<tr>";
                        //                     echo "<td>Jika membeli {$itemA['item']} maka akan membeli {$itemB['item']} </td>";
                        //                     echo "<td>" . round($supportA, 2) . "%</td>";
                        //                     echo "<td>" . round($supportAB, 2) . "%</td>";
                        //                     echo "<td>" . round($confidenceAB, 2) . "%</td>";
                        //                     echo "</tr>";

                        //                     // Tambahkan kombinasi ke array kombinasi unik
                        //                     $kombinasi_unik[] = $sortedCombination;
                        //                 }
                        //             }
                        //         }
                        //     }
                        // }
                        ?>
                    </table>
                </div>
            </div>
        </div>

        <?php include "scripts.php"; ?>
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="//cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

        <script>
            $(document).ready(function() {
                $('#example').DataTable();
            });
        </script>
</body>

</html>