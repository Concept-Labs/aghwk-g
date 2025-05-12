<?php
namespace App\Controller;

use App\Core\Controller;
use App\Model\Parcel;
use App\Model\Block;
use App\Core\Database;

class ParcelController extends Controller
{
    /** List with filters, sort, pagination, page size */
    public function index(): void
    {
        session_start();
        $uid = $_SESSION['uid'] ?? 0;
        if (!$uid) { header('Location: /?q=auth/login'); exit; }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPageOpts = [10,25,50];
        $perPage = in_array((int)($_GET['limit'] ?? 10), $perPageOpts) ? (int)$_GET['limit'] : 10;

        $sort  = $_GET['sort'] ?? 'name';
        $dir   = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
        $allowedSort = ['name','status','created_at'];
        if (!in_array($sort, $allowedSort)) $sort = 'name';

        $nameFilter = trim($_GET['f_name'] ?? '');
        $addrFilter = trim($_GET['f_address'] ?? '');

        $model  = new Parcel();
        $params = ['account_id'=>$uid];
        $where  = 'WHERE account_id = :account_id';

        if ($nameFilter !== '') {
            $where .= ' AND name LIKE :fname';
            $params['fname'] = '%' . $nameFilter . '%';
        }
        if ($addrFilter !== '') {
            $where .= ' AND (street LIKE :addr OR city LIKE :addr OR state LIKE :addr OR zip LIKE :addr)';
            $params['addr'] = '%' . $addrFilter . '%';
        }

        $total = $model->countWhere($where, $params);
        $perPage = max(1,$perPage); // safeguard
        $pages = max(1, (int)ceil($total / $perPage));
        if ($page > $pages) $page = $pages;
        $offset = ($page - 1) * $perPage;

        $parcels = $model->listWhere($where, $params, $sort, $dir, $perPage, $offset);

        // Block counts
        $blockCounts = [];
        if ($parcels) {
            $db  = Database::connect();
            $ids = array_column($parcels, 'id');
            $in  = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT parcel_id, COUNT(*) cnt FROM block WHERE parcel_id IN ($in) GROUP BY parcel_id");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll() as $row) {
                $blockCounts[$row['parcel_id']] = $row['cnt'];
            }
        }

        $this->render('parcel/index', [
            'parcels'     => $parcels,
            'counts'      => $blockCounts,
            'page'        => $page,
            'pages'       => $pages,
            'perPage'     => $perPage,
            'perPageOpts' => $perPageOpts,
            'sort'        => $sort,
            'dir'         => $dir,
            'nameFilter'  => $nameFilter,
            'addrFilter'  => $addrFilter
        ]);
    }

    /** Add new parcel + blocks (unchanged) */
    public function add(): void
    {
        session_start();
        $uid = $_SESSION['uid'] ?? 0;
        if (!$uid) { header('Location: /?q=auth/login'); exit; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $parcelModel = new Parcel();
            $blockModel  = new Block();

            $pdata = array_intersect_key($_POST, array_flip(['name','street','city','state','zip','status','notes']));
            $pdata['account_id'] = $uid;
            $pid = $parcelModel->create($pdata);

            foreach ($_POST['blocks'] ?? [] as $b) {
                $b['parcel_id']  = $pid;
                $b['account_id'] = $uid;
                $blockModel->create($b);
            }
            header('Location: /?q=parcel/index');
            exit;
        }

        $this->render('parcel/add');
    }

    /** CSV export */
    public function export(): void
    {
        session_start();
        $uid = $_SESSION['uid'] ?? 0;
        if (!$uid) { header('Location: /?q=auth/login'); exit; }

        $model = new Parcel();
        $rows  = $model->listWhere('WHERE account_id = :id', ['id'=>$uid], 'name', 'ASC', 100000, 0);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="parcels.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Name','Street','City','State','ZIP','Status','Created']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'],$r['name'],$r['street'],$r['city'],$r['state'],$r['zip'],$r['status'],$r['created_at']]);
        }
        fclose($out);
    }
}
