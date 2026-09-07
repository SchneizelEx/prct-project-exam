<?php
/** @var PDO $pdo ต้องกำหนดก่อน include */
$approvedSchedule = projects_public_approved_schedule($pdo);
?>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">หัวข้อที่ได้รับการอนุมัติให้เข้าสอบ</h5>
        <?php if (!$approvedSchedule): ?>
            <p class="text-muted mb-0">ยังไม่มีหัวข้อที่ได้รับการอนุมัติให้เข้าสอบ</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                    <tr><th>วันสอบ</th><th>เวลา</th><th>หัวข้อโครงงาน</th><th>ครูที่ปรึกษา</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($approvedSchedule as $s): ?>
                        <tr>
                            <td><?= h($s['exam_date']) ?></td>
                            <td><?= h($s['slot_start']) ?> - <?= h($s['slot_end']) ?></td>
                            <td><?= h($s['title']) ?></td>
                            <td><?= h($s['advisor_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
