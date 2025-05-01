# Python Analytics Scripts for Dental Clinic System

This directory contains Python scripts that are used to generate analytics data for the Dental Clinic System.

## Requirements

To use these scripts, you need the following Python packages:

```bash
pip install mysql-connector-python pandas matplotlib
```

## Analytics.py

This script generates analytics data for the dental clinic system. It connects to the MySQL database, runs various queries, and generates charts based on the results.

### Usage

```bash
python analytics.py [analysis_type]
```

Where `analysis_type` can be:
- `appointments`: Generates analytics about appointments
- `payments`: Generates analytics about payments

### Output

The script outputs JSON data that is consumed by the PHP application. It also generates charts that are saved to the `assets/images/analytics` directory.

## Directory Structure

- `scripts/`: Contains Python scripts
- `assets/images/analytics/`: Contains generated chart images

## Troubleshooting

If you encounter issues running the scripts, ensure:

1. Python is properly installed and in your PATH
2. Required Python packages are installed
3. MySQL is running and accessible
4. Proper database credentials are used in the script
