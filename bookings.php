<?php
session_start();
include 'db.php';

// Fetch all upcoming bookings
$upcoming_bookings_query = $conn->prepare("
    SELECT 
        sb.booking_id, sb.customer_name, sb.booking_date, sb.start_time, sb.end_time, st.table_name
    FROM 
        snooker_bookings sb
    JOIN 
        snooker_tables st ON sb.table_id = st.id
    WHERE 
        sb.booking_date >= CURDATE()
    ORDER BY 
        sb.booking_date ASC, sb.start_time ASC
");
$upcoming_bookings_query->execute();
$upcoming_bookings_result = $upcoming_bookings_query->get_result();
$upcoming_bookings_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snooker | View Bookings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    
    <!-- Layout -->
    <div class="flex">
        <?php include 'layout/sidebar.php'; ?>

        <main class="flex-1 ml-0 lg:ml-64 pt-20 p-6 sm:p-8">
            <?php include 'layout/header.php'; ?>

            <h1 class="text-3xl font-extrabold mb-6 border-b pb-2 mt-9 text-center">📅 Upcoming Reservations</h1>

            <div class="bg-white shadow-xl p-6 sm:p-8 rounded-2xl border">

                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold">All Active Bookings</h2>
                    <a href="./add_booking.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
                        + Add New Booking
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Table</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Time Slot</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">

                            <?php if ($upcoming_bookings_result->num_rows > 0): ?>
                                <?php while ($booking = $upcoming_bookings_result->fetch_assoc()): ?>
                                    <tr class="hover:bg-blue-50 transition">
                                        
                                        <td class="px-6 py-4"><?php echo $booking['table_name']; ?></td>

                                        <td class="px-6 py-4"><?php echo $booking['customer_name']; ?></td>

                                        <td class="px-6 py-4">
                                            <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                        </td>

                                        <td class="px-6 py-4 text-green-600 font-bold">
                                            <?= date('g:i A', strtotime($booking['start_time'])); ?> -
                                            <?= date('g:i A', strtotime($booking['end_time'])); ?>
                                        </td>

                                        <td class="px-6 py-4 text-right">
                                            <div class="flex gap-2 justify-end">

                                                <!-- EDIT -->
                                                <button 
                                                    onclick="openEditModal(<?= $booking['booking_id']; ?>)"
                                                    class="bg-yellow-500 text-white px-3 py-1 rounded-full text-xs">
                                                    Edit
                                                </button>

                                                <!-- DELETE -->
                                                <button 
                                                    onclick="deleteBooking(<?= $booking['booking_id']; ?>)"
                                                    class="bg-red-500 text-white px-3 py-1 rounded-full text-xs">
                                                    Delete
                                                </button>

                                            </div>
                                        </td>

                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>

                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center bg-gray-50 text-gray-500">
                                        No upcoming reservations found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center">
        <div class="bg-white w-full max-w-lg p-6 rounded-xl shadow-lg">

            <h2 class="text-xl font-bold mb-4">Edit Booking</h2>

            <form id="editBookingForm">

                <input type="hidden" id="edit_booking_id">

                <label class="font-semibold text-sm">Customer Name</label>
                <input id="edit_customer" class="w-full border rounded p-2 mb-3">

                <label class="font-semibold text-sm">Date</label>
                <input type="date" id="edit_date" class="w-full border rounded p-2 mb-3">

                <label class="font-semibold text-sm">Start Time</label>
                <input type="time" id="edit_start" class="w-full border rounded p-2 mb-3">

                <label class="font-semibold text-sm">End Time</label>
                <input type="time" id="edit_end" class="w-full border rounded p-2 mb-3">

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 rounded">
                        Cancel
                    </button>

                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
                        Update
                    </button>
                </div>

            </form>

        </div>
    </div>


    <!-- AJAX SCRIPT -->
    <script>
        // ---------------- DELETE BOOKING ----------------
        function deleteBooking(id) {
            if (!confirm("Are you sure you want to delete this booking?")) return;

            fetch("booking_delete.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "id=" + id
            })
            .then(res => res.text())
            .then(msg => {
                alert(msg);
                location.reload();
            });
        }

        // ---------------- OPEN EDIT MODAL ----------------
        function openEditModal(id) {
            fetch("booking_get.php?id=" + id)
            .then(res => res.json())
            .then(data => {
                document.getElementById("edit_booking_id").value = data.booking_id;
                document.getElementById("edit_customer").value = data.customer_name;
                document.getElementById("edit_date").value = data.booking_date;
                document.getElementById("edit_start").value = data.start_time;
                document.getElementById("edit_end").value = data.end_time;

                document.getElementById("editModal").classList.remove("hidden");
            });
        }

        function closeEditModal() {
            document.getElementById("editModal").classList.add("hidden");
        }

        // ---------------- UPDATE BOOKING ----------------
        document.getElementById("editBookingForm").addEventListener("submit", function(e){
            e.preventDefault();

            const form = new URLSearchParams();
            form.append("id", document.getElementById("edit_booking_id").value);
            form.append("customer_name", document.getElementById("edit_customer").value);
            form.append("booking_date", document.getElementById("edit_date").value);
            form.append("start_time", document.getElementById("edit_start").value);
            form.append("end_time", document.getElementById("edit_end").value);

            fetch("booking_update.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: form.toString()
            })
            .then(res => res.text())
            .then(msg => {
                alert(msg);
                closeEditModal();
                location.reload();
            });
        });
    </script>

</body>
</html>
