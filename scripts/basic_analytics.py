import sys
import json
import sqlite3
import os
from datetime import datetime

# Function to connect to the database using SQLite as a fallback
def connect_to_database():
    try:
        # Get MySQL connection details from config file if it exists
        config_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), "config", "db_config.php")
        host = "localhost"
        username = "root"
        password = ""
        database = "clinicdentalsystem"
        
        # Create SQLite database path
        db_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), "data", "analytics.db")
        os.makedirs(os.path.dirname(db_path), exist_ok=True)
        
        # Connect to SQLite as a fallback
        connection = sqlite3.connect(db_path)
        connection.row_factory = sqlite3.Row
        return connection
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

# Function to generate appointment analytics
def appointment_analytics():
    conn = connect_to_database()
    cursor = conn.cursor()
    
    try:
        # Create tables if they don't exist - simulate appointments table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS sample_appointments (
            id INTEGER PRIMARY KEY,
            patient_name TEXT,
            doctor_name TEXT,
            appointment_date TEXT,
            status TEXT
        )
        ''')
        
        # Check if we have sample data, if not add some
        cursor.execute("SELECT COUNT(*) as count FROM sample_appointments")
        if cursor.fetchone()["count"] == 0:
            # Add sample data
            sample_data = [
                ("John Doe", "Dr. Smith", "2023-09-01 10:00:00", "completed"),
                ("Jane Doe", "Dr. Smith", "2023-09-02 11:00:00", "pending"),
                ("Bob Smith", "Dr. Jones", "2023-09-03 14:00:00", "confirmed"),
                ("Alice Johnson", "Dr. Jones", "2023-09-04 15:00:00", "cancelled"),
                ("Mark Wilson", "Dr. Smith", "2023-09-05 09:00:00", "confirmed"),
                ("Sarah Brown", "Dr. Brown", "2023-09-06 10:00:00", "confirmed"),
                ("Michael Davis", "Dr. Jones", "2023-09-07 13:00:00", "pending"),
                ("Emily White", "Dr. Smith", "2023-09-08 14:00:00", "completed"),
                ("David Miller", "Dr. Brown", "2023-09-09 11:00:00", "confirmed"),
                ("Jennifer Taylor", "Dr. Jones", "2023-09-10 16:00:00", "pending"),
            ]
            cursor.executemany(
                "INSERT INTO sample_appointments (patient_name, doctor_name, appointment_date, status) VALUES (?, ?, ?, ?)",
                sample_data
            )
            conn.commit()
        
        # Get appointment counts by status
        cursor.execute("""
            SELECT status, COUNT(*) as count 
            FROM sample_appointments 
            GROUP BY status
        """)
        status_counts = [dict(row) for row in cursor.fetchall()]
        
        # Get appointment counts by day of week
        cursor.execute("""
            SELECT strftime('%w', appointment_date) as day_num,
                   CASE strftime('%w', appointment_date)
                       WHEN '0' THEN 'Sunday'
                       WHEN '1' THEN 'Monday'
                       WHEN '2' THEN 'Tuesday'
                       WHEN '3' THEN 'Wednesday'
                       WHEN '4' THEN 'Thursday'
                       WHEN '5' THEN 'Friday'
                       WHEN '6' THEN 'Saturday'
                   END as day_name,
                   COUNT(*) as count
            FROM sample_appointments
            GROUP BY day_num
            ORDER BY day_num
        """)
        day_counts = [dict(row) for row in cursor.fetchall()]
        
        # Get top doctors by appointment count
        cursor.execute("""
            SELECT doctor_name, COUNT(*) as appointment_count
            FROM sample_appointments
            GROUP BY doctor_name
            ORDER BY appointment_count DESC
        """)
        top_doctors = [dict(row) for row in cursor.fetchall()]
        
        # Generate summary data
        total_appointments = sum(item["count"] for item in status_counts)
        pending_count = next((item["count"] for item in status_counts if item["status"] == "pending"), 0)
        confirmed_count = next((item["count"] for item in status_counts if item["status"] == "confirmed"), 0)
        cancelled_count = next((item["count"] for item in status_counts if item["status"] == "cancelled"), 0)
        
        return {
            "total_appointments": total_appointments,
            "status_counts": status_counts,
            "day_counts": day_counts,
            "top_doctors": top_doctors,
            "summary": {
                "pending": pending_count,
                "confirmed": confirmed_count,
                "cancelled": cancelled_count
            }
        }
    except Exception as e:
        return {"error": str(e)}
    finally:
        conn.close()

# Function to generate payment analytics
def payment_analytics():
    conn = connect_to_database()
    cursor = conn.cursor()
    
    try:
        # Create tables if they don't exist - simulate payments table
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS sample_payments (
            id INTEGER PRIMARY KEY,
            amount REAL,
            payment_method TEXT,
            payment_date TEXT,
            status TEXT
        )
        ''')
        
        # Check if we have sample data, if not add some
        cursor.execute("SELECT COUNT(*) as count FROM sample_payments")
        if cursor.fetchone()["count"] == 0:
            # Add sample data
            sample_data = [
                (100.00, "cash", "2023-09-01", "completed"),
                (150.00, "card", "2023-09-02", "completed"),
                (200.00, "gcash", "2023-09-03", "pending"),
                (250.00, "cash", "2023-09-04", "completed"),
                (300.00, "card", "2023-09-05", "completed"),
                (350.00, "gcash", "2023-09-06", "pending"),
                (400.00, "cash", "2023-09-07", "completed"),
                (450.00, "card", "2023-09-08", "completed"),
                (500.00, "gcash", "2023-09-09", "rejected"),
                (550.00, "cash", "2023-09-10", "completed"),
            ]
            cursor.executemany(
                "INSERT INTO sample_payments (amount, payment_method, payment_date, status) VALUES (?, ?, ?, ?)",
                sample_data
            )
            conn.commit()
        
        # Get payment counts by method
        cursor.execute("""
            SELECT payment_method, COUNT(*) as count, SUM(amount) as total
            FROM sample_payments
            GROUP BY payment_method
        """)
        method_stats = [dict(row) for row in cursor.fetchall()]
        
        # Get payment counts by status
        cursor.execute("""
            SELECT status, COUNT(*) as count, SUM(amount) as total
            FROM sample_payments
            GROUP BY status
        """)
        status_stats = [dict(row) for row in cursor.fetchall()]
        
        # Get monthly revenue (simplified with sample data)
        cursor.execute("""
            SELECT strftime('%Y-%m', payment_date) as month,
                   SUM(amount) as revenue
            FROM sample_payments
            WHERE status = 'completed'
            GROUP BY month
            ORDER BY month DESC
            LIMIT 6
        """)
        monthly_revenue = [dict(row) for row in cursor.fetchall()]
        
        # Calculate total revenue
        cursor.execute("""
            SELECT SUM(amount) as total_revenue
            FROM sample_payments
            WHERE status = 'completed'
        """)
        total_result = cursor.fetchone()
        total_revenue = float(total_result["total_revenue"]) if total_result["total_revenue"] else 0
        
        # Calculate current month revenue
        current_month = datetime.now().strftime('%Y-%m')
        cursor.execute("""
            SELECT SUM(amount) as month_revenue
            FROM sample_payments
            WHERE status = 'completed'
            AND strftime('%Y-%m', payment_date) = ?
        """, (current_month,))
        month_result = cursor.fetchone()
        month_revenue = float(month_result["month_revenue"]) if month_result["month_revenue"] else 0
        
        return {
            "total_revenue": total_revenue,
            "month_revenue": month_revenue,
            "method_stats": method_stats,
            "status_stats": status_stats,
            "monthly_revenue": monthly_revenue
        }
    except Exception as e:
        return {"error": str(e)}
    finally:
        conn.close()

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No analysis type specified"}))
        return
    
    analysis_type = sys.argv[1]
    
    if analysis_type == "appointments":
        result = appointment_analytics()
    elif analysis_type == "payments":
        result = payment_analytics()
    else:
        result = {"error": "Invalid analysis type"}
    
    print(json.dumps(result, default=str))

if __name__ == "__main__":
    main()
