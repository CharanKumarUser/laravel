{{-- Template: Analytics Page - Auto-generated --}}
@extends('layouts.system-app')
@section('title', 'Analytics')
@section('top-style')
@endsection
@push('scripts')
<script>
        window.addEventListener("load", () => {
            window.skeleton.charts();
        });
    </script>
@endpush
@section('content')
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb">
        <div class="my-auto mb-2">
            <h3 class="mb-1">Analytics</h3>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item "><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><a href="#">Analytics</a></li>
                </ol>
            </nav>
        </div>
        <div></div>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="live-time-container head-icons">
                    <span class="live-time-icon me-2"><i class="fa-thin fa-clock"></i></span>
                    <div class="live-time"></div>
                </div>
            <div class="ms-2 head-icons">
                <a href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header"><i class="ti ti-chevrons-up"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-12">
        {{--************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (START) <<<                                  *
        *                                                                                                  *
        ************************************************************************************************--}}
         <h1>ECharts Bar, Pie, and Donut with Complex Datasets</h1>
            <style>
                .grid-container {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(clamp(250px, 45vw, 350px), 1fr));
                    gap: clamp(10px, 2vw, 15px);
                    padding: clamp(10px, 2vw, 15px);
                    box-sizing: border-box;
                }

                .chart-container {
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    padding: clamp(10px, 2vw, 15px);
                    text-align: center;
                    box-sizing: border-box;
                    width: 100%;
                    height: auto;
                }
            </style>
            <h1>Got-It HR Software: Comprehensive HR Analytics Dashboard</h1>
            <!-- Pie Chart with Maximum Settings -->
            <div id="complex-pie" class="chart-container" data-chart="pie" data-title="Feature Usage in Dashboard"
                data-subtitle="Frequency of Settings in Got-It HR Dashboard (Pie)"
                data-labels="animation,area,avoidLabelOverlap,brush,confidenceband,effect,export,gradient,labels,legend,progress,ring,rose,showparent,smooth,stacked,tooltip,vertical,visualmap,zoom,align:center,offset:5,offset:10,rotate:30,rotate:45,truncate:10,truncate:15"
                data-set1="50,3,50,0,1,1,0,36,50,36,3,3,1,0,3,2,47,3,2,1,3,23,27,5,18,29,21"
                data-settings="confidenceband,tooltip,legend,labels,gradient,animation,avoidLabelOverlap,rose,area,export,zoom,brush"
                data-label-settings="offset:10,truncate:5,align:left,rotate:45"
                data-rich="bold,italic,underline,shadow,size:14,color:#2C3A47"
                data-colors="#FF6B6B,#4ECDC4,#45B7D1,#96CEB4,#FFEEAD,#D4A5A5,#9B59B6,#3498DB,#E74C3C,#2ECC71,#F39C12,#E91E63,#00BCD4,#8E44AD,#1ABC9C,#FFC107,#FF5722,#00E676,#C2185B,#7C4DFF,#FD7272,#55E6C1,#3B3B98,#FDA7DC,#2C3A47,#F8EFBA,#BDC581"
                data-size="100%x600px" data-xaxis-name="Feature" data-yaxis-name="Frequency" data-unit="count"
                data-min="0" data-max="100" data-symbol="circle" style="width: 100%; height: 600px;">
            </div>

            <!-- Bar Chart with Maximum Settings -->
            <div id="complex-bar" class="chart-container" data-chart="bar" data-title="Feature Usage in Dashboard"
                data-subtitle="Frequency of Settings in Got-It HR Dashboard (Bar)"
                data-labels="animation,area,avoidLabelOverlap,brush,confidenceband,effect,export,gradient,labels,legend,progress,ring,rose,showparent,smooth,stacked,tooltip,vertical,visualmap,zoom,align:center,offset:5,offset:10,rotate:30,rotate:45,truncate:10,truncate:15"
                data-set1="50,3,50,0,1,1,0,36,50,36,3,3,1,0,3,2,47,3,2,1,3,23,27,5,18,29,21"
                data-set2="45,2,48,0,0,2,0,30,45,30,2,2,0,0,2,1,40,2,1,0,2,20,25,4,15,25,18"
                data-set3="5,1,2,0,1,1,0,6,5,6,1,1,1,0,1,1,7,1,1,1,1,3,2,1,3,4,3"
                data-set2-labels="Primary Set,Secondary Set,Confidence Band"
                data-settings="tooltip,legend,labels,gradient,animation,stacked,zoom,brush,markline,markarea,horizontal,timeaxis,logaxis"
                data-label-settings="offset:10,truncate:15,align:center,rotate:45"
                data-rich="bold,italic,underline,shadow,size:14,color:#2C3A47"
                data-colors="#FF6B6B,#4ECDC4,#45B7D1,#96CEB4,#FFEEAD,#D4A5A5,#9B59B6,#3498DB,#E74C3C,#2ECC71,#F39C12,#E91E63,#00BCD4,#8E44AD,#1ABC9C,#FFC107,#FF5722,#00E676,#C2185B,#7C4DFF,#FD7272,#55E6C1,#3B3B98,#FDA7DC,#2C3A47,#F8EFBA,#BDC581"
                data-size="100%x600px" data-xaxis-name="Feature" data-yaxis-name="Frequency" data-unit="count"
                data-min="0" data-max="100" data-symbol="rect" style="width: 100%; height: 600px;">
            </div>
            <div class="grid-container">
                <!-- 1. Simple Bar: Attendance Rate by Department -->
                <div class="chart-container">
                    <h3>Attendance Rate by Department</h3>
                    <div data-chart="bar" data-title="Department Attendance Rates" data-labels="IT,HR,Sales,Operations"
                        data-set1="95,88,92,85" data-settings="tooltip,legend,labels,gradient,animation,avoidLabelOverlap"
                        data-xaxis-name="Department" data-yaxis-name="Attendance (%)"
                        data-label-settings="rotate:45,offset:10,truncate:15" data-rich="size:10,bold"
                        data-colors="#FF6B6B,#4ECDC4,#45B7D1,#96CEB4" data-size="100%"></div>
                </div>
                <!-- 2. Simple Pie: Leave Type Distribution -->
                <div class="chart-container">
                    <h3>Leave Type Distribution</h3>
                    <div data-chart="pie" data-title="Leave Types Breakdown" data-labels="Sick,Vacation,Personal,Unpaid"
                        data-set1="30,40,20,10" data-settings="tooltip,legend,labels,animation,avoidLabelOverlap"
                        data-label-settings="offset:10,truncate:10" data-rich="size:10,bold"
                        data-colors="#FFEEAD,#D4A5A5,#9B59B6,#3498DB" data-size="100%"></div>
                </div>
                <!-- 3. Simple Line: Monthly Payroll Trend -->
                <div class="chart-container">
                    <h3>Monthly Payroll Trend</h3>
                    <div data-chart="line" data-title="Payroll Over Time" data-labels="Jan,Feb,Mar,Apr,May"
                        data-set1="500000,510000,490000,520000,505000"
                        data-settings="tooltip,legend,smooth,area,animation,avoidLabelOverlap" data-xaxis-name="Month"
                        data-yaxis-name="Payroll (INR)" data-label-settings="rotate:30,offset:5" data-rich="size:10"
                        data-colors="#E74C3C" data-size="100%"></div>
                </div>
                <!-- 4. Stacked Bar: Asset Maintenance Costs -->
                <div class="chart-container">
                    <h3>Asset Maintenance Costs by Type</h3>
                    <div data-chart="bar" data-title="Asset Maintenance Costs"
                        data-labels="Laptops,Desks,Monitors,Printers" data-set1="50000,20000,30000,15000"
                        data-set2="30000,15000,20000,10000" data-set2-labels="Repair,Replacement"
                        data-settings="stacked,gradient,labels,tooltip,legend,zoom,animation,avoidLabelOverlap"
                        data-xaxis-name="Asset Type" data-yaxis-name="Cost (INR)"
                        data-label-settings="rotate:45,offset:10,truncate:15" data-rich="size:10,bold"
                        data-colors="#2ECC71,#F39C12" data-size="100%"></div>
                </div>
                <!-- 5. Rose Pie: Leave Approval Status -->
                <div class="chart-container">
                    <h3>Leave Approval Status</h3>
                    <div data-chart="pie" data-title="Leave Approval Rates" data-labels="Approved,Pending,Rejected"
                        data-set1="70,20,10"
                        data-settings="rose,gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="offset:10,truncate:10" data-rich="size:10,bold,color:#FFFFFF,shadow"
                        data-colors="#32CD32,#FFD700,#FF4500" data-size="100%"></div>
                </div>
                <!-- 6. Donut: Asset Allocation Status -->
                <div class="chart-container">
                    <h3>Asset Allocation Status</h3>
                    <div data-chart="donut" data-title="Asset Distribution"
                        data-labels="Allocated,In Maintenance,Available" data-set1="60,20,20"
                        data-settings="gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="offset:10,truncate:10" data-rich="size:10,italic,underline"
                        data-colors="#4682B4,#FF6347,#FFD700" data-size="100%"></div>
                </div>
                <!-- 7. Heatmap: Biometric Attendance Failures -->
                <div class="chart-container">
                    <h3>Biometric Attendance Failures</h3>
                    <div data-chart="heatmap" data-title="Biometric Scan Failures" data-labels-x="Mon,Tue,Wed,Thu,Fri"
                        data-labels-y="08:00,12:00,16:00"
                        data-set1="0,0,5;0,1,3;0,2,2;1,0,4;1,1,6;1,2,3;2,0,5;2,1,4;2,2,2;3,0,3;3,1,5;3,2,4;4,0,2;4,1,3;4,2,6"
                        data-settings="visualmap,gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,color:#FFFFFF,bold"
                        data-xaxis-name="Day" data-yaxis-name="Hour" data-colors="#FF6B6B,#4ECDC4,#45B7D1"
                        data-size="100%"></div>
                </div>
                <!-- 8. Gauge: Employee Productivity Score -->
                <div class="chart-container">
                    <h3>Employee Productivity Score</h3>
                    <div data-chart="gauge" data-title="Productivity Score" data-set1="82"
                        data-settings="progress,labels,animation,ring,avoidLabelOverlap" data-label-settings="offset:5"
                        data-rich="size:12,bold,color:#32CD32" data-unit="%" data-min="0" data-max="100"
                        data-colors="#91c7ae,#63869e,#c23531" data-size="100%"></div>
                </div>
                <!-- 9. Treemap: Asset Utilization by Department -->
                <div class="chart-container">
                    <h3>Asset Utilization by Department</h3>
                    <div data-chart="treemap" data-title="Asset Utilization Breakdown"
                        data-set1='[{"name":"IT","value":60},{"name":"HR","value":20},{"name":"Sales","value":30},{"name":"Operations","value":40}]'
                        data-settings="labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="truncate:15,align:center" data-rich="size:10,shadow"
                        data-colors="#96CEB4,#FFEEAD,#D4A5A5,#9B59B6" data-size="100%"></div>
                </div>
                <!-- 10. Sankey: Leave Request Workflow -->
                <div class="chart-container">
                    <h3>Leave Request Workflow</h3>
                    <div data-chart="sankey" data-title="Leave Process Flow"
                        data-set1='[{"nodes":[{"name":"Submitted"},{"name":"Manager Review"},{"name":"HR Approval"},{"name":"Approved"},{"name":"Rejected"}],"links":[{"source":"Submitted","target":"Manager Review","value":100},{"source":"Manager Review","target":"HR Approval","value":80},{"source":"Manager Review","target":"Rejected","value":20},{"source":"HR Approval","target":"Approved","value":70},{"source":"HR Approval","target":"Rejected","value":10}]}]'
                        data-settings="vertical,gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:15" data-rich="size:10,bold,color:#FFD700"
                        data-colors="#3498DB,#E74C3C,#2ECC71,#F39C12,#E91E63" data-size="100%"></div>
                </div>
                <!-- 11. Custom: Biometric Attendance Accuracy -->
                <div class="chart-container">
                    <h3>Biometric Attendance Accuracy</h3>
                    <div data-chart="custom" data-title="Biometric Scan Accuracy"
                        data-set1="1,0.95;2,0.92;3,0.98;4,0.90;5,0.96"
                        data-render-item="const data = [api.value(0), api.value(1)]; if (data[0] == null || data[1] == null) return { type: 'group' }; const [x, y] = api.coord(data); return { type: 'circle', shape: { cx: x, cy: y, r: data[1] > 0.95 ? 15 : 10 }, style: { fill: data[1] > 0.95 ? '#00ff00' : '#ff4500' } };"
                        data-settings="gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,bold"
                        data-xaxis-name="Scan Attempt" data-yaxis-name="Accuracy" data-colors="#00ff00,#ff4500"
                        data-size="100%"></div>
                </div>
                <!-- 12. Scatter: Attendance vs Hours Worked -->
                <div class="chart-container">
                    <h3>Attendance vs Hours Worked</h3>
                    <div data-chart="scatter" data-title="Attendance vs Hours" data-set1="1,8;2,7;3,9;4,6;5,8"
                        data-settings="gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,italic" data-xaxis-name="Day"
                        data-yaxis-name="Hours Worked" data-colors="#FF6B6B,#4ECDC4" data-size="100%"></div>
                </div>
                <!-- 13. Line: Attendance Trend with Confidence Band -->
                <div class="chart-container">
                    <h3>Attendance Trend with Confidence Band</h3>
                    <div data-chart="line" data-title="Attendance Trends" data-labels="Jan,Feb,Mar,Apr,May"
                        data-set1="90,92,88,95,93" data-set2="92,94,90,97,95" data-set3="88,90,86,93,91"
                        data-set2-labels="Mean,Upper,Lower"
                        data-settings="confidenceband,smooth,gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="rotate:30,offset:5" data-rich="size:10,color:#800080"
                        data-xaxis-name="Month" data-yaxis-name="Attendance (%)" data-colors="#2ECC71,#F39C12,#E91E63"
                        data-size="100%"></div>
                </div>
                <!-- 14. Bar: Leave Rejection Reasons -->
                <div class="chart-container">
                    <h3>Leave Rejection Reasons</h3>
                    <div data-chart="bar" data-title="Leave Rejection Causes"
                        data-labels="Insufficient Balance,Policy Violation,Overlap" data-set1="40,30,20"
                        data-settings="gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="rotate:45,offset:10,truncate:15" data-rich="size:10,bold"
                        data-xaxis-name="Reason" data-yaxis-name="Count" data-colors="#FF4500,#FFD700,#4682B4"
                        data-size="100%"></div>
                </div>
                <!-- 15. Gauge: Asset Utilization Rate -->
                <div class="chart-container">
                    <h3>Asset Utilization Rate</h3>
                    <div data-chart="gauge" data-title="Asset Utilization" data-set1="78"
                        data-settings="progress,labels,animation,ring,avoidLabelOverlap" data-label-settings="offset:5"
                        data-rich="size:12,bold,color:#32CD32" data-unit="%" data-min="0" data-max="100"
                        data-colors="#91c7ae,#63869e,#c23531" data-size="100%"></div>
                </div>
                <!-- 16. Sankey: Asset Allocation Workflow -->
                <div class="chart-container">
                    <h3>Asset Allocation Workflow</h3>
                    <div data-chart="sankey" data-title="Asset Allocation Process"
                        data-set1='[{"nodes":[{"name":"Request"},{"name":"Approval"},{"name":"Allocated"},{"name":"In Use"},{"name":"Returned"}],"links":[{"source":"Request","target":"Approval","value":100},{"source":"Approval","target":"Allocated","value":90},{"source":"Approval","target":"Returned","value":10},{"source":"Allocated","target":"In Use","value":80},{"source":"In Use","target":"Returned","value":70}]}]'
                        data-settings="vertical,gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:15" data-rich="size:10,bold,color:#4682B4"
                        data-colors="#3498DB,#E74C3C,#2ECC71,#F39C12,#E91E63" data-size="100%"></div>
                </div>
                <!-- 17. Custom: Attendance Anomaly Detection -->
                <div class="chart-container">
                    <h3>Attendance Anomaly Detection</h3>
                    <div data-chart="custom" data-title="Attendance Anomalies" data-set1="1,95;2,88;3,92;4,60;5,94"
                        data-render-item="const data = [api.value(0), api.value(1)]; if (data[0] == null || data[1] == null) return { type: 'group' }; const [x, y] = api.coord(data); return { type: 'circle', shape: { cx: x, cy: y, r: data[1] < 80 ? 15 : 10 }, style: { fill: data[1] < 80 ? '#ff0000' : '#00ff00' } };"
                        data-settings="gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,bold" data-xaxis-name="Day"
                        data-yaxis-name="Attendance (%)" data-colors="#00ff00,#ff0000" data-size="100%"></div>
                </div>
                <!-- 18. Radar: Employee Performance Metrics -->
                <div class="chart-container">
                    <h3>Employee Performance Metrics</h3>
                    <div data-chart="radar" data-title="Performance by Department"
                        data-labels="Productivity,Engagement,Attendance" data-set1="80,85,90" data-set2="70,80,85"
                        data-set2-labels="IT,HR"
                        data-settings="area,gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10" data-colors="#FF6B6B,#4ECDC4"
                        data-size="100%"></div>
                </div>
                <!-- 19. Funnel: Recruitment Pipeline -->
                <div class="chart-container">
                    <h3>Recruitment Pipeline</h3>
                    <div data-chart="funnel" data-title="Hiring Funnel" data-labels="Applied,Screened,Interviewed,Hired"
                        data-set1="1000,500,200,50"
                        data-settings="gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:15" data-rich="size:10,bold"
                        data-colors="#E74C3C,#2ECC71,#F39C12,#E91E63" data-size="100%"></div>
                </div>
                <!-- 20. Candlestick: Payroll Fluctuations -->
                <div class="chart-container">
                    <h3>Payroll Fluctuations</h3>
                    <div data-chart="candlestick" data-title="Monthly Payroll Changes" data-labels="Jan,Feb,Mar,Apr"
                        data-set1="500000,510000,505000,510000;490000,495000,485000,490000;510000,520000,515000,520000;505000,510000,500000,505000"
                        data-settings="labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="rotate:30,offset:5" data-rich="size:10" data-xaxis-name="Month"
                        data-yaxis-name="Payroll (INR)" data-colors="#FF6B6B,#4ECDC4" data-size="100%"></div>
                </div>
                <!-- 21. Boxplot: Attendance Distribution -->
                <div class="chart-container">
                    <h3>Attendance Distribution</h3>
                    <div data-chart="boxplot" data-title="Attendance by Department" data-labels="IT,HR,Sales"
                        data-set1="80,85,90,95,100;70,75,80,85,90;85,90,95,100,105"
                        data-settings="labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="rotate:45,offset:5" data-rich="size:10" data-xaxis-name="Department"
                        data-yaxis-name="Attendance (%)" data-colors="#45B7D1,#96CEB4,#FFEEAD" data-size="100%"></div>
                </div>
                <!-- 22. Parallel: Employee Metrics Comparison -->
                <div class="chart-container">
                    <h3>Employee Metrics Comparison</h3>
                    <div data-chart="parallel" data-title="Employee Metrics"
                        data-labels="Productivity,Engagement,Attendance" data-set1="80,85,90;70,80,85;75,90,88"
                        data-settings="labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10"
                        data-colors="#FF6B6B,#4ECDC4,#45B7D1" data-size="100%"></div>
                </div>
                <!-- 23. Lines: Employee Commute Patterns -->
                <div class="chart-container">
                    <h3>Employee Commute Patterns</h3>
                    <div data-chart="lines" data-title="Commute Routes"
                        data-set1='[{"name":"Route 1","coords":[[1,2],[3,4],[5,6]]},{"name":"Route 2","coords":[[2,3],[4,5],[6,7]]}]'
                        data-settings="effect,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,bold" data-xaxis-name="X"
                        data-yaxis-name="Y" data-colors="#E74C3C,#2ECC71" data-size="100%"></div>
                </div>
                <!-- 24. Graph: Employee Collaboration Network -->
                <div class="chart-container">
                    <h3>Employee Collaboration Network</h3>
                    <div data-chart="graph" data-title="Collaboration Network"
                        data-set1='[{"name":"Alice"},{"name":"Bob"},{"name":"Charlie"}]'
                        data-set2='[{"source":"Alice","target":"Bob","value":5},{"source":"Bob","target":"Charlie","value":3}]'
                        data-settings="labels,tooltip,animation,roam,draggable,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:15" data-rich="size:10,bold" data-colors="#F39C12,#E91E63"
                        data-size="100%"></div>
                </div>
                <!-- 25. Tree: Department Hierarchy -->
                <div class="chart-container">
                    <h3>Department Hierarchy</h3>
                    <div data-chart="tree" data-title="Org Structure"
                        data-set1='[{"name":"Company","children":[{"name":"IT","children":[{"name":"Dev"},{"name":"QA"}]},{"name":"HR"}]}]'
                        data-settings="labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:15" data-rich="size:10,bold" data-colors="#FF6B6B,#4ECDC4"
                        data-size="100%"></div>
                </div>
                <!-- 26. ThemeRiver: Leave Trends Over Time -->
                <div class="chart-container">
                    <h3>Leave Trends Over Time</h3>
                    <div data-chart="themeriver" data-title="Leave Trends"
                        data-set1="2025-01-01,30,Sick;2025-02-01,40,Sick;2025-01-01,20,Vacation;2025-02-01,25,Vacation"
                        data-settings="labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,bold" data-colors="#E74C3C,#2ECC71"
                        data-size="100%"></div>
                </div>
                <!-- 27. PictorialBar: Asset Usage Frequency -->
                <div class="chart-container">
                    <h3>Asset Usage Frequency</h3>
                    <div data-chart="pictorialbar" data-title="Asset Usage" data-labels="Laptops,Desks,Monitors"
                        data-set1="50,30,20" data-symbol="circle"
                        data-settings="labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="rotate:45,offset:5" data-rich="size:10" data-xaxis-name="Asset Type"
                        data-yaxis-name="Usage Count" data-colors="#FF6B6B,#4ECDC4,#45B7D1" data-size="100%"></div>
                </div>
                <!-- 28. Bar: Overtime Hours by Department -->
                <div class="chart-container">
                    <h3>Overtime Hours by Department</h3>
                    <div data-chart="bar" data-title="Overtime Hours" data-labels="IT,HR,Sales,Operations"
                        data-set1="100,50,80,120"
                        data-settings="gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="rotate:45,offset:10,truncate:15" data-rich="size:10,bold"
                        data-xaxis-name="Department" data-yaxis-name="Hours" data-colors="#96CEB4" data-size="100%">
                    </div>
                </div>
                <!-- 29. Pie: Employee Turnover by Department -->
                <div class="chart-container">
                    <h3>Employee Turnover by Department</h3>
                    <div data-chart="pie" data-title="Turnover Rates" data-labels="IT,HR,Sales,Operations"
                        data-set1="15,10,20,5" data-settings="gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="offset:10,truncate:10" data-rich="size:10,italic"
                        data-colors="#FFEEAD,#D4A5A5,#9B59B6,#3498DB" data-size="100%"></div>
                </div>
                <!-- 30. Line: Biometric System Uptime -->
                <div class="chart-container">
                    <h3>Biometric System Uptime</h3>
                    <div data-chart="line" data-title="System Uptime" data-labels="Jan,Feb,Mar,Apr,May"
                        data-set1="99.5,99.8,99.2,99.7,99.6"
                        data-settings="smooth,area,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="rotate:30,offset:5" data-rich="size:10" data-xaxis-name="Month"
                        data-yaxis-name="Uptime (%)" data-colors="#E74C3C" data-size="100%"></div>
                </div>
                <!-- 31. Stacked Bar: Leave Types by Department -->
                <div class="chart-container">
                    <h3>Leave Types by Department</h3>
                    <div data-chart="bar" data-title="Leave Types per Department" data-labels="IT,HR,Sales,Operations"
                        data-set1="20,15,25,10" data-set2="10,20,15,5" data-set3="5,10,5,15"
                        data-set2-labels="Sick,Vacation,Personal"
                        data-settings="stacked,gradient,labels,tooltip,legend,zoom,animation,avoidLabelOverlap"
                        data-label-settings="rotate:45,offset:10,truncate:15" data-rich="size:10,bold"
                        data-xaxis-name="Department" data-yaxis-name="Leave Count" data-colors="#2ECC71,#F39C12,#E91E63"
                        data-size="100%"></div>
                </div>
                <!-- 32. Donut: Employee Satisfaction Levels -->
                <div class="chart-container">
                    <h3>Employee Satisfaction Levels</h3>
                    <div data-chart="donut" data-title="Satisfaction Breakdown" data-labels="High,Medium,Low"
                        data-set1="60,30,10" data-settings="gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="offset:10,truncate:10" data-rich="size:10,italic,underline"
                        data-colors="#4682B4,#FF6347,#FFD700" data-size="100%"></div>
                </div>
                <!-- 33. Heatmap: Asset Usage by Day -->
                <div class="chart-container">
                    <h3>Asset Usage by Day</h3>
                    <div data-chart="heatmap" data-title="Asset Usage Patterns" data-labels-x="Mon,Tue,Wed,Thu,Fri"
                        data-labels-y="Laptops,Desks,Monitors"
                        data-set1="0,0,80;0,1,75;0,2,70;1,0,50;1,1,55;1,2,60;2,0,70;2,1,65;2,2,75;3,0,60;3,1,70;3,2,65;4,0,80;4,1,75;4,2,70"
                        data-settings="visualmap,gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,color:#FFFFFF,bold"
                        data-xaxis-name="Day" data-yaxis-name="Asset Type" data-colors="#FF6B6B,#4ECDC4,#45B7D1"
                        data-size="100%"></div>
                </div>
                <!-- 34. Gauge: Training Completion Rate -->
                <div class="chart-container">
                    <h3>Training Completion Rate</h3>
                    <div data-chart="gauge" data-title="Training Completion" data-set1="85"
                        data-settings="progress,labels,animation,ring,avoidLabelOverlap" data-label-settings="offset:5"
                        data-rich="size:12,bold,color:#FF4500" data-unit="%" data-min="0" data-max="100"
                        data-colors="#91c7ae,#63869e,#c23531" data-size="100%"></div>
                </div>
                <!-- 35. Treemap: Payroll Distribution by Role -->
                <div class="chart-container">
                    <h3>Payroll Distribution by Role</h3>
                    <div data-chart="treemap" data-title="Payroll by Role"
                        data-set1='[{"name":"Developers","value":2000000},{"name":"Managers","value":1500000},{"name":"Support","value":1000000},{"name":"Others","value":500000}]'
                        data-settings="labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="truncate:15,align:center" data-rich="size:10,shadow"
                        data-colors="#96CEB4,#FFEEAD,#D4A5A5,#9B59B6" data-size="100%"></div>
                </div>
                <!-- 36. Sankey: Recruitment Workflow -->
                <div class="chart-container">
                    <h3>Recruitment Workflow</h3>
                    <div data-chart="sankey" data-title="Recruitment Process Flow"
                        data-set1='[{"nodes":[{"name":"Applied"},{"name":"Screened"},{"name":"Interviewed"},{"name":"Hired"},{"name":"Rejected"}],"links":[{"source":"Applied","target":"Screened","value":1000},{"source":"Screened","target":"Interviewed","value":500},{"source":"Screened","target":"Rejected","value":500},{"source":"Interviewed","target":"Hired","value":200},{"source":"Interviewed","target":"Rejected","value":300}]}]'
                        data-settings="vertical,gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:15" data-rich="size:10,bold,color:#FFD700"
                        data-colors="#3498DB,#E74C3C,#2ECC71,#F39C12,#E91E63" data-size="100%"></div>
                </div>
                <!-- 37. Custom: Leave Approval Time -->
                <div class="chart-container">
                    <h3>Leave Approval Time</h3>
                    <div data-chart="custom" data-title="Approval Time Analysis" data-set1="1,2;2,3;3,1;4,4;5,2"
                        data-render-item="const data = [api.value(0), api.value(1)]; if (data[0] == null || data[1] == null) return { type: 'group' }; const [x, y] = api.coord(data); return { type: 'rect', shape: { x: x-10, y: y-10, width: 20, height: 20 }, style: { fill: data[1] > 3 ? '#ff4500' : '#00ff00' } };"
                        data-settings="gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,bold" data-xaxis-name="Request ID"
                        data-yaxis-name="Days to Approve" data-colors="#00ff00,#ff4500" data-size="100%"></div>
                </div>
                <!-- 38. Scatter: Employee Engagement vs Performance -->
                <div class="chart-container">
                    <h3>Engagement vs Performance</h3>
                    <div data-chart="scatter" data-title="Engagement vs Performance"
                        data-set1="80,85;75,70;90,95;60,65;85,80"
                        data-settings="gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,italic"
                        data-xaxis-name="Engagement (%)" data-yaxis-name="Performance (%)" data-colors="#FF6B6B,#4ECDC4"
                        data-size="100%"></div>
                </div>
                <!-- 39. Line: Overtime Trends -->
                <div class="chart-container">
                    <h3>Overtime Trends</h3>
                    <div data-chart="line" data-title="Overtime Hours" data-labels="Jan,Feb,Mar,Apr,May"
                        data-set1="100,120,90,110,130"
                        data-settings="smooth,area,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="rotate:30,offset:5" data-rich="size:10" data-xaxis-name="Month"
                        data-yaxis-name="Overtime Hours" data-colors="#E74C3C" data-size="100%"></div>
                </div>
                <!-- 40. Bar: Training Hours by Department -->
                <div class="chart-container">
                    <h3>Training Hours by Department</h3>
                    <div data-chart="bar" data-title="Training Hours" data-labels="IT,HR,Sales,Operations"
                        data-set1="120,60,90,110"
                        data-settings="gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="rotate:45,offset:10,truncate:15" data-rich="size:10,bold"
                        data-xaxis-name="Department" data-yaxis-name="Hours" data-colors="#96CEB4" data-size="100%">
                    </div>
                </div>
                <!-- 41. Pie: Recruitment Source Effectiveness -->
                <div class="chart-container">
                    <h3>Recruitment Source Effectiveness</h3>
                    <div data-chart="pie" data-title="Recruitment Sources"
                        data-labels="Job Boards,Referrals,Agencies,Direct" data-set1="40,30,20,10"
                        data-settings="gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="offset:10,truncate:10" data-rich="size:10,italic"
                        data-colors="#FFEEAD,#D4A5A5,#9B59B6,#3498DB" data-size="100%"></div>
                </div>
                <!-- 42. Line: Employee Retention Rate -->
                <div class="chart-container">
                    <h3>Employee Retention Rate</h3>
                    <div data-chart="line" data-title="Retention Over Time" data-labels="Jan,Feb,Mar,Apr,May"
                        data-set1="95,94,93,92,91" data-settings="smooth,area,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="rotate:30,offset:5" data-rich="size:10" data-xaxis-name="Month"
                        data-yaxis-name="Retention (%)" data-colors="#E74C3C" data-size="100%"></div>
                </div>
                <!-- 43. Bar: Performance Review Scores -->
                <div class="chart-container">
                    <h3>Performance Review Scores</h3>
                    <div data-chart="bar" data-title="Performance Scores" data-labels="Q1,Q2,Q3,Q4"
                        data-set1="85,88,90,87" data-settings="gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="rotate:45,offset:10,truncate:15" data-rich="size:10,bold"
                        data-xaxis-name="Quarter" data-yaxis-name="Score" data-colors="#2ECC71" data-size="100%"></div>
                </div>
                <!-- 44. Donut: Department Budget Allocation -->
                <div class="chart-container">
                    <h3>Department Budget Allocation</h3>
                    <div data-chart="donut" data-title="Budget Breakdown" data-labels="IT,HR,Sales,Operations"
                        data-set1="40,20,30,10" data-settings="gradient,labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="offset:10,truncate:10" data-rich="size:10,italic,underline"
                        data-colors="#4682B4,#FF6347,#FFD700,#96CEB4" data-size="100%"></div>
                </div>
                <!-- 45. Heatmap: Employee Shift Preferences -->
                <div class="chart-container">
                    <h3>Employee Shift Preferences</h3>
                    <div data-chart="heatmap" data-title="Shift Preferences" data-labels-x="Morning,Afternoon,Night"
                        data-labels-y="IT,HR,Sales"
                        data-set1="0,0,50;0,1,30;0,2,20;1,0,40;1,1,60;1,2,10;2,0,30;2,1,50;2,2,20"
                        data-settings="visualmap,gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,color:#FFFFFF,bold"
                        data-xaxis-name="Shift" data-yaxis-name="Department" data-colors="#FF6B6B,#4ECDC4,#45B7D1"
                        data-size="100%"></div>
                </div>
                <!-- 46. Gauge: Employee Engagement Score -->
                <div class="chart-container">
                    <h3>Employee Engagement Score</h3>
                    <div data-chart="gauge" data-title="Engagement Score" data-set1="75"
                        data-settings="progress,labels,animation,ring,avoidLabelOverlap" data-label-settings="offset:5"
                        data-rich="size:12,bold,color:#FF4500" data-unit="%" data-min="0" data-max="100"
                        data-colors="#91c7ae,#63869e,#c23531" data-size="100%"></div>
                </div>
                <!-- 47. Treemap: Office Space Utilization -->
                <div class="chart-container">
                    <h3>Office Space Utilization</h3>
                    <div data-chart="treemap" data-title="Space Utilization"
                        data-set1='[{"name":"Cubicles","value":50},{"name":"Meeting Rooms","value":30},{"name":"Common Areas","value":20}]'
                        data-settings="labels,tooltip,legend,animation,avoidLabelOverlap"
                        data-label-settings="truncate:15,align:center" data-rich="size:10,shadow"
                        data-colors="#96CEB4,#FFEEAD,#D4A5A5" data-size="100%"></div>
                </div>
                <!-- 48. Sankey: Employee Onboarding Workflow -->
                <div class="chart-container">
                    <h3>Employee Onboarding Workflow</h3>
                    <div data-chart="sankey" data-title="Onboarding Process"
                        data-set1='[{"nodes":[{"name":"Hired"},{"name":"Orientation"},{"name":"Training"},{"name":"Active"},{"name":"Dropped"}],"links":[{"source":"Hired","target":"Orientation","value":100},{"source":"Orientation","target":"Training","value":80},{"source":"Orientation","target":"Dropped","value":20},{"source":"Training","target":"Active","value":70},{"source":"Training","target":"Dropped","value":10}]}]'
                        data-settings="vertical,gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:15" data-rich="size:10,bold,color:#FFD700"
                        data-colors="#3498DB,#E74C3C,#2ECC71,#F39C12" data-size="100%"></div>
                </div>
                <!-- 49. Custom: Payroll Processing Time -->
                <div class="chart-container">
                    <h3>Payroll Processing Time</h3>
                    <div data-chart="custom" data-title="Payroll Processing" data-set1="1,4;2,3;3,5;4,2;5,4"
                        data-render-item="const data = [api.value(0), api.value(1)]; if (data[0] == null || data[1] == null) return { type: 'group' }; const [x, y] = api.coord(data); return { type: 'rect', shape: { x: x-10, y: y-10, width: 20, height: 20 }, style: { fill: data[1] > 3 ? '#ff4500' : '#00ff00' } };"
                        data-settings="gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,bold" data-xaxis-name="Batch ID"
                        data-yaxis-name="Days" data-colors="#00ff00,#ff4500" data-size="100%"></div>
                </div>
                <!-- 50. Scatter: Training Hours vs Performance -->
                <div class="chart-container">
                    <h3>Training Hours vs Performance</h3>
                    <div data-chart="scatter" data-title="Training vs Performance"
                        data-set1="20,85;30,90;15,80;25,88;40,95"
                        data-settings="gradient,labels,tooltip,animation,avoidLabelOverlap"
                        data-label-settings="offset:5,truncate:10" data-rich="size:10,italic"
                        data-xaxis-name="Training Hours" data-yaxis-name="Performance (%)" data-colors="#FF6B6B,#4ECDC4"
                        data-size="100%"></div>
                </div>
            </div>





            <div data-chart="pie" data-set1="30, 20, 50" data-labels="Apples, Bananas, Oranges"
                data-colors="#ff0000, #00ff00, #0000ff" data-title="Fruit Distribution"
                data-settings="labels,tooltip,legend" data-size="600x400"></div>
            <div class="chart-grid">
                <!-- Bar: Stacked, Gradient, Zoom, Labels, Rich Text -->
                <div class="chart-container">
                    <h3>Bar (Stacked, Gradient, Zoom)</h3>
                    <div data-chart="bar" data-set1="120, 132, 101, 134, 90, 230, 210"
                        data-set2="220, 182, 191, 234, 290, 330, 310" data-set3="150, 212, 201, 154, 190, 330, 410"
                        data-labels="Jan, Feb, Mar, Apr, May, Jun, Jul" data-set2-labels="Product A, Product B, Product C"
                        data-settings="stacked,gradient,labels,tooltip,legend,zoom,brush,horizontal,markline,markarea"
                        data-rich="bold,color:#FF4500,size:14,underline,shadow" data-title="Monthly Sales by Product"
                        data-subtitle="2025 Performance" data-xaxis-name="Sales (USD)" data-yaxis-name="Month"
                        data-colors="#FF6347, #32CD32, #4682B4" data-size="100%x400"></div>
                </div>
                <!-- Bar: Waterfall, Labels, Gradient -->
                <div class="chart-container">
                    <h3>Bar (Waterfall)</h3>
                    <div data-chart="bar" data-set1="100, 120, 80, 150, 90, 200, 50"
                        data-labels="Start, Jan, Feb, Mar, Apr, May, End"
                        data-settings="waterfall,gradient,labels,tooltip,legend,markline"
                        data-rich="italic,color:#008080,size:12,shadow" data-title="Profit Waterfall"
                        data-xaxis-name="Month" data-yaxis-name="Profit (USD)" data-size="600x400"></div>
                </div>
                <!-- Bar: Matrix, Multiple Datasets -->
                <div class="chart-container">
                    <h3>Bar (Matrix)</h3>
                    <div data-chart="bar" data-set1="120, 132, 101, 134, 90" data-set2="220, 182, 191, 234, 290"
                        data-set3="150, 212, 201, 154, 190" data-labels="Q1, Q2, Q3, Q4, Q5"
                        data-set2-labels="Region A, Region B, Region C"
                        data-settings="matrix,gradient,labels,tooltip,legend,zoom" data-rich="bold,size:14,color:#800080"
                        data-title="Quarterly Sales by Region" data-xaxis-name="Quarter" data-yaxis-name="Sales (USD)"
                        data-colors="#FFD700, #ADFF2F, #FF4500" data-size="600x400"></div>
                </div>
                <!-- Pie: Rose, Gradient, Labels, Rich Text -->
                <div class="chart-container">
                    <h3>Pie (Rose, Gradient, Labels)</h3>
                    <div data-chart="pie" data-set1="335, 310, 234, 135, 448, 270, 190"
                        data-labels="Direct, Search, Social, Ads, Email, Referral, Other"
                        data-settings="rose,gradient,labels,tooltip,legend,animation,export,avoidLabelOverlap"
                        data-rich="bold,size:16,shadow" data-title="Traffic Sources"
                        data-subtitle="Website Analytics 2025"
                        data-colors="#FF6347, #FFD700, #ADFF2F, #4682B4, #FF4500, #32CD32, #800080" data-size="100%">
                    </div>
                </div>
                <!-- Pie: Simple with Labels -->
                <div class="chart-container">
                    <h3>Pie (Simple, Labels)</h3>
                    <div data-chart="pie" data-set1="200, 180, 150, 120, 100, 80, 50"
                        data-labels="Product A, Product B, Product C, Product D, Product E, Product F, Product G"
                        data-colors="#FFD700, #ADFF2F, #FF4500, #FFD700, #ADFF2F, #FF4500"
                        data-settings="labels,tooltip,legend,animation" data-rich="italic,size:12"
                        data-title="Product Sales Distribution" data-size="600x400"></div>
                </div>
                <!-- Donut: Gradient, Labels, Rich Text -->
                <div class="chart-container">
                    <h3>Donut (Gradient, Labels)</h3>
                    <div data-chart="donut" data-set1="500, 400, 300, 200, 100, 50, 25"
                        data-labels="North, South, East, West, Central, Northeast, Southwest"
                        data-settings="gradient,labels,tooltip,legend,animation,export"
                        data-rich="bold,color:#FFD700,size:14,underline" data-title="Regional Revenue"
                        data-subtitle="2025 Breakdown"
                        data-colors="#FF6347, #FFD700, #ADFF2F, #4682B4, #FF4500, #32CD32, #800080" data-size="600x400">
                    </div>
                </div>
                <!-- Donut: Rose, Minimal Settings -->
                <div class="chart-container">
                    <h3>Donut (Rose, Minimal)</h3>
                    <div data-chart="donut" data-set1="300, 250, 200, 150, 100, 75, 50"
                        data-labels="Category A, Category B, Category C, Category D, Category E, Category F, Category G"
                        data-settings="rose,gradient,labels" data-title="Category Distribution" data-size="600x400">
                    </div>
                </div>
                <!-- Bar: Negative Values, Gradient -->
                <div class="chart-container">
                    <h3>Bar (Negative Values)</h3>
                    <div data-chart="bar" data-set1="120, -132, 101, -134, 90, 230, -210"
                        data-labels="Jan, Feb, Mar, Apr, May, Jun, Jul"
                        data-settings="gradient,labels,tooltip,legend,markline" data-rich="bold,color:#FF4500,size:12"
                        data-title="Profit/Loss by Month" data-xaxis-name="Month" data-yaxis-name="Amount (USD)"
                        data-size="600x400"></div>
                </div>
                <!-- Pie: Empty Dataset Edge Case -->
                <div class="chart-container">
                    <h3>Pie (Empty Dataset)</h3>
                    <div data-chart="pie" data-set1="" data-labels="A, B, C" data-settings="labels,tooltip"
                        data-title="Empty Pie" data-size="600x400"></div>
                </div>
                <!-- Bar: Single Value Edge Case -->
                <div class="chart-container">
                    <h3>Bar (Single Value)</h3>
                    <div data-chart="bar" data-set1="100" data-labels="Single" data-settings="labels,tooltip"
                        data-title="Single Value Bar" data-size="600x400"></div>
                </div>
                <!-- Pie: Minimal -->
                <div class="chart-container">
                    <h3>Pie (Minimal)</h3>
                    <div data-chart="pie" data-set1="10, 20, 30" data-size="400x300"></div>
                </div>
                <!-- Pie: Full Settings -->
                <div class="chart-container">
                    <h3>Pie (Full Settings)</h3>
                    <div data-chart="pie" data-set1="15, 25, 35, 45" data-labels="Apple, Banana, Cherry, Date"
                        data-settings="gradient,labels,tooltip,legend,rose,animation,export"
                        data-rich="bold,color:#FF4500,size:14,underline,shadow" data-title="Fruit Sales"
                        data-subtitle="2025" data-colors="#FF6347, #FFD700, #ADFF2F, #4682B4" data-size="400x300">
                    </div>
                </div>
                <!-- Donut: With Settings -->
                <div class="chart-container">
                    <h3>Donut (Gradient, Labels)</h3>
                    <div data-chart="donut" data-set1="40, 30, 20, 10" data-labels="Q1, Q2, Q3, Q4"
                        data-settings="gradient,labels,tooltip,legend,animation" data-rich="italic,underline,size:12"
                        data-title="Quarterly Revenue" data-size="400x300"></div>
                </div>
                <!-- Bar: Minimal -->
                <div class="chart-container">
                    <h3>Bar (Minimal)</h3>
                    <div data-chart="bar" data-set1="5, 10, 15, 20" data-size="400x300"></div>
                </div>
                <!-- Bar: Complex -->
                <div class="chart-container">
                    <h3>Bar (Stacked, Matrix, Zoom)</h3>
                    <div data-chart="bar" data-set1="10, 20, 30, 40" data-set2="15, 25, 35, 45"
                        data-set3="5, 15, 25, 35" data-labels="Mon, Tue, Wed, Thu" data-set2-labels="S1, S2, S3"
                        data-settings="stacked,gradient,labels,tooltip,legend,zoom,brush,horizontal,matrix,markline,markarea"
                        data-rich="shadow,size:12,color:#0000FF" data-xaxis-name="Value" data-yaxis-name="Day"
                        data-colors="#FF4500, #32CD32, #4682B4" data-size="400x300"></div>
                </div>
                <!-- Bar: Waterfall -->
                <div class="chart-container">
                    <h3>Bar (Waterfall)</h3>
                    <div data-chart="bar" data-set1="10, 20, 30, 40" data-labels="Jan, Feb, Mar, Apr"
                        data-settings="waterfall,gradient,labels" data-rich="bold" data-size="400x300"></div>
                </div>
                <!-- Line: Minimal -->
                <div class="chart-container">
                    <h3>Line (Minimal)</h3>
                    <div data-chart="line" data-set1="5, 10, 15, 20" data-size="400x300"></div>
                </div>
                <!-- Line: Confidence Band -->
                <div class="chart-container">
                    <h3>Line (Confidence Band, Smooth)</h3>
                    <div data-chart="line" data-set1="10, 20, 30, 40" data-set2="12, 22, 32, 42"
                        data-set3="8, 18, 28, 38" data-labels="Q1, Q2, Q3, Q4" data-set2-labels="Mean, Upper, Lower"
                        data-settings="confidenceband,smooth,gradient,labels,area,zoom,export"
                        data-rich="color:#800080,size:14" data-size="400x300"></div>
                </div>
                <!-- Line: Time Axis -->
                <div class="chart-container">
                    <h3>Line (Time Axis, Step)</h3>
                    <div data-chart="line" data-set1="10, 20, 30, 40"
                        data-labels="2025-01-01, 2025-02-01, 2025-03-01, 2025-04-01"
                        data-settings="timeaxis,step,gradient,labels" data-rich="italic" data-size="400x300"></div>
                </div>
                <!-- Scatter: Minimal -->
                <div class="chart-container">
                    <h3>Scatter (Minimal)</h3>
                    <div data-chart="scatter" data-set1="1,2;3,4;5,6" data-size="400x300"></div>
                </div>
                <!-- EffectScatter: Bubble, Fisheye -->
                <div class="chart-container">
                    <h3>EffectScatter (Bubble, Fisheye)</h3>
                    <div data-chart="effectscatter" data-set1="1,2,10;3,4,20;5,6,30"
                        data-settings="bubble,fisheye,gradient,labels,effect,animation,tooltip" data-rich="bold,size:12"
                        data-xaxis-name="X" data-yaxis-name="Y" data-size="400x300"></div>
                </div>
                <!-- Scatter: Matrix -->
                <div class="chart-container">
                    <h3>Scatter (Matrix)</h3>
                    <div data-chart="scatter" data-set1="1,2;3,4" data-set2="2,3;4,5" data-set2-labels="Set1, Set2"
                        data-settings="matrix,gradient,labels" data-rich="color:#FF0000" data-size="400x300"></div>
                </div>
                <!-- Radar: Full Settings -->
                <div class="chart-container">
                    <h3>Radar (Area, Gradient)</h3>
                    <div data-chart="radar" data-set1="65, 80, 90, 70" data-set2="70, 85, 95, 75"
                        data-labels="Speed, Power, Accuracy, Endurance" data-set2-labels="Player1, Player2"
                        data-settings="area,gradient,labels,tooltip,legend,export" data-rich="size:14,shadow"
                        data-size="400x300"></div>
                </div>
                <!-- Gauge: Minimal -->
                <div class="chart-container">
                    <h3>Gauge (Minimal)</h3>
                    <div data-chart="gauge" data-set1="75" data-size="400x300"></div>
                </div>
                <!-- Gauge: Car -->
                <div class="chart-container">
                    <h3>Gauge (Car, Rich Text)</h3>
                    <div data-chart="gauge" data-set1="120" data-settings="car,labels,progress,tick,split,animation"
                        data-rich="bold,color:#FF0000,size:20,underline" data-unit="km/h" data-title="Speedometer"
                        data-size="400x300"></div>
                </div>
                <!-- Gauge: Temperature -->
                <div class="chart-container">
                    <h3>Gauge (Temperature)</h3>
                    <div data-chart="gauge" data-set1="25" data-settings="temperature,labels,progress"
                        data-rich="italic" data-unit="°C" data-size="400x300"></div>
                </div>
                <!-- Gauge: Grade -->
                <div class="chart-container">
                    <h3>Gauge (Grade)</h3>
                    <div data-chart="gauge" data-set1="85" data-settings="grade,labels" data-rich="bold"
                        data-size="400x300"></div>
                </div>
                <!-- Gauge: Multi-Title -->
                <div class="chart-container">
                    <h3>Gauge (Multi-Title)</h3>
                    <div data-chart="gauge" data-set1="80" data-set2="90" data-set3="70"
                        data-set2-labels="Metric1, Metric2, Metric3" data-settings="multi-title,gradient,labels"
                        data-rich="underline,size:12" data-size="400x300"></div>
                </div>
                <!-- Gauge: Barometer -->
                <div class="chart-container">
                    <h3>Gauge (Barometer)</h3>
                    <div data-chart="gauge" data-set1="1013" data-settings="barometer,labels,progress"
                        data-rich="color:#4682B4" data-unit="hPa" data-size="400x300"></div>
                </div>
                <!-- Heatmap: Full Settings -->
                <div class="chart-container">
                    <h3>Heatmap (VisualMap)</h3>
                    <div data-chart="heatmap" data-set1="0,0,10;0,1,20;1,0,15;1,1,25" data-labels-x="Mon, Tue"
                        data-labels-y="AM, PM" data-settings="visualmap,gradient,labels,tooltip"
                        data-rich="color:#FFFFFF,bold" data-xaxis-name="Day" data-yaxis-name="Time" data-size="400x300">
                    </div>
                </div>
                <!-- Candlestick: With Breaks -->
                <div class="chart-container">
                    <h3>Candlestick (Breaks)</h3>
                    <div data-chart="candlestick" data-set1="20,30,10,15;30,40,25,30;40,50,35,45"
                        data-labels="Day1, Day2, Day3" data-settings="breaks,labels,gradient" data-rich="size:12"
                        data-size="400x300"></div>
                </div>
                <!-- Funnel: Full Settings -->
                <div class="chart-container">
                    <h3>Funnel (Gradient)</h3>
                    <div data-chart="funnel" data-set1="100, 80, 60, 40, 20"
                        data-labels="Lead, Prospect, Opportunity, Customer, Sale"
                        data-settings="gradient,labels,tooltip,animation" data-rich="bold,color:#32CD32"
                        data-title="Sales Funnel" data-size="400x300"></div>
                </div>
                <!-- Treemap: Show Parent -->
                <div class="chart-container">
                    <h3>Treemap (Show Parent)</h3>
                    <div data-chart="treemap"
                        data-set1='{"name":"A","value":40};{"name":"B","value":30};{"name":"C","value":20}'
                        data-settings="showparent,gradient,labels,tooltip" data-rich="size:12,shadow"
                        data-size="400x300"></div>
                </div>
                <!-- Sunburst: Emphasis -->
                <div class="chart-container">
                    <h3>Sunburst (Emphasis)</h3>
                    <div data-chart="sunburst"
                        data-set1='{"name":"Root","value":100,"children":[{"name":"A","value":30},{"name":"B","children":[{"name":"B1","value":10},{"name":"B2","value":20}]}]}'
                        data-settings="emphasis,gradient,labels,animation" data-rich="italic,size:12"
                        data-size="400x300"></div>
                </div>
                <!-- Sankey: Vertical -->
                <div class="chart-container">
                    <h3>Sankey (Vertical, Left)</h3>
                    <div data-chart="sankey"
                        data-set1='{"nodes":[{"name":"A"},{"name":"B"},{"name":"C"}],"links":[{"source":"A","target":"B","value":10},{"source":"B","target":"C","value":5}]}'
                        data-settings="vertical,left,gradient,labels,tooltip" data-rich="bold,color:#FFD700"
                        data-size="400x300"></div>
                </div>
                <!-- Boxplot: Full Settings -->
                <div class="chart-container">
                    <h3>Boxplot</h3>
                    <div data-chart="boxplot" data-set1="10,20,30,40,50;15,25,35,45,55;5,15,25,35,45"
                        data-labels="Group1, Group2, Group3" data-settings="labels,gradient,tooltip" data-rich="size:14"
                        data-xaxis-name="Group" data-yaxis-name="Value" data-size="400x300">
                    </div>
                </div>
                <!-- Parallel: Full Settings -->
                <div class="chart-container">
                    <h3>Parallel</h3>
                    <div data-chart="parallel" data-set1="1,2,3,4;4,5,6,7;7,8,9,10"
                        data-labels="Axis1, Axis2, Axis3, Axis4" data-settings="labels,gradient,tooltip"
                        data-rich="color:#800080,bold" data-size="400x300"></div>
                </div>
                <!-- Lines: Effect -->
                <div class="chart-container">
                    <h3>Lines (Effect)</h3>
                    <div data-chart="lines"
                        data-set1='{"coords":"0,0;1,1;2,2","name":"Line1"};{"coords":"2,2;3,3;4,4","name":"Line2"}'
                        data-settings="effect,gradient,labels,tooltip,animation" data-rich="bold,size:12"
                        data-xaxis-name="X" data-yaxis-name="Y" data-size="400x300"></div>
                </div>
                <!-- Graph: Draggable -->
                <div class="chart-container">
                    <h3>Graph (Draggable, Roam)</h3>
                    <div data-chart="graph"
                        data-set1='{"name":"Node1","value":10};{"name":"Node2","value":20};{"name":"Node3","value":30}'
                        data-set2='{"source":"Node1","target":"Node2","value":1};{"source":"Node2","target":"Node3","value":2}'
                        data-settings="draggable,gradient,labels,roam,tooltip" data-rich="size:12,shadow"
                        data-size="400x300"></div>
                </div>
                <!-- Tree: Radial -->
                <div class="chart-container">
                    <h3>Tree (Radial, Polyline)</h3>
                    <div data-chart="tree"
                        data-set1='{"name":"Root","children":[{"name":"A","value":10},{"name":"B","children":[{"name":"B1","value":5}]}]}'
                        data-settings="radial,polyline,gradient,labels,tooltip" data-rich="italic,size:12"
                        data-size="400x300"></div>
                </div>
                <!-- ThemeRiver: Full Settings -->
                <div class="chart-container">
                    <h3>ThemeRiver</h3>
                    <div data-chart="themeriver"
                        data-set1='2025-01-01,10,Stream1;2025-01-02,15,Stream1;2025-01-01,5,Stream2;2025-01-02,8,Stream2'
                        data-settings="gradient,labels,tooltip,animation" data-rich="bold,color:#FF6347"
                        data-size="400x300"></div>
                </div>
                <!-- PictorialBar: Dotted -->
                <div class="chart-container">
                    <h3>PictorialBar (Dotted, Triangle)</h3>
                    <div data-chart="pictorialbar" data-set1="10, 20, 30, 40" data-labels="A, B, C, D"
                        data-symbol="triangle" data-settings="dotted,gradient,labels,tooltip"
                        data-rich="color:#008000,size:14" data-xaxis-name="Category" data-yaxis-name="Value"
                        data-size="400x300"></div>
                </div>
                <!-- Custom: Rectangles -->
                <div class="chart-container">
                    <h3>Custom (Rectangles)</h3>
                    <div data-chart="custom" data-set1="1,2;3,4;5,6;7,8"
                        data-render-item="if (!params || !Array.isArray(params.data) || params.data.length < 2 || !api) return { type: 'group' }; const [x, y] = api.coord(params.data); return { type: 'rect', shape: { x: x-15, y: y-15, width: 30, height: 30 }, style: { fill: params.data[0] > 4 ? '#ff0000' : '#00ff00', stroke: '#000', lineWidth: 2 } };"
                        data-settings="gradient,labels,tooltip" data-rich="bold,size:12" data-xaxis-name="X"
                        data-yaxis-name="Y" data-size="400x300"></div>
                </div>
                <!-- Custom: Circles with Gradient -->
                <div class="chart-container">
                    <h3>Custom (Circles)</h3>
                    <div data-chart="custom" data-set1="1,2;3,4;5,6"
                        data-render-item="if (!params || !Array.isArray(params.data) || params.data.length < 2 || !api) return { type: 'group' }; const [x, y] = api.coord(params.data); return { type: 'circle', shape: { cx: x, cy: y, r: Math.abs(params.data[1]) * 5 }, style: { fill: params.data[1] > 3 ? '#ff4500' : '#4682b4' } };"
                        data-settings="gradient,labels" data-rich="italic" data-size="400x300"></div>
                </div>
                <!-- Edge Case: Empty Dataset -->
                <div class="chart-container">
                    <h3>Bar (Empty Dataset)</h3>
                    <div data-chart="bar" data-set1="" data-size="400x300"></div>
                </div>
                <!-- Edge Case: Single Value -->
                <div class="chart-container">
                    <h3>Gauge (Single Value)</h3>
                    <div data-chart="gauge" data-set1="42" data-settings="labels" data-size="400x300"></div>
                </div>
                <!-- Edge Case: Missing Labels -->
                <div class="chart-container">
                    <h3>Pie (Missing Labels)</h3>
                    <div data-chart="pie" data-set1="10, 20, 30" data-settings="labels,gradient" data-rich="size:12"
                        data-size="400x300"></div>
                </div>
                <!-- Auto-Detect: Scatter -->
                <div class="chart-container">
                    <h3>Auto-Detect (Scatter)</h3>
                    <div data-chart-auto data-set1="1,2;3,4;5,6" data-settings="gradient,labels,tooltip"
                        data-rich="italic,size:12" data-size="400x300"></div>
                </div>
                <!-- Auto-Detect: Gauge -->
                <div class="chart-container">
                    <h3>Auto-Detect (Gauge)</h3>
                    <div data-chart-auto data-set1="85" data-settings="progress,labels" data-rich="bold"
                        data-unit="%" data-size="400x300"></div>
                </div>
                <!-- Auto-Detect: Sankey -->
                <div class="chart-container">
                    <h3>Auto-Detect (Sankey)</h3>
                    <div data-chart-auto
                        data-set1='{"nodes":[{"name":"A"},{"name":"B"}],"links":[{"source":"A","target":"B","value":10}]}'
                        data-settings="gradient,labels" data-rich="size:12" data-size="400x300"></div>
                </div>
                <!-- Pie Chart: Simple -->
                <div class="chart-container">
                    <h3>Pie Chart (Simple)</h3>
                    <div data-chart="pie" data-set1="10, 20, 30" data-labels="A, B, C" data-size="400x300"></div>
                </div>
                <!-- Pie Chart: With Settings -->
                <div class="chart-container">
                    <h3>Pie Chart (Gradient, Labels, Rich Text)</h3>
                    <div data-chart="pie" data-set1="15, 25, 35" data-labels="Apple, Banana, Cherry"
                        data-settings="gradient,labels,tooltip,legend,rose,animation"
                        data-rich="bold,color:#FF4500,size:14" data-title="Fruit Sales" data-subtitle="2025"
                        data-colors="#FF6347, #FFD700, #ADFF2F" data-size="400x300"></div>
                </div>
                <!-- Donut Chart -->
                <div class="chart-container">
                    <h3>Donut Chart (With Settings)</h3>
                    <div data-chart="donut" data-set1="40, 30, 20, 10" data-labels="Q1, Q2, Q3, Q4"
                        data-settings="gradient,labels,tooltip,legend,animation" data-rich="italic,underline"
                        data-title="Quarterly Revenue" data-size="400x300"></div>
                </div>
                <!-- Bar Chart: Simple -->
                <div class="chart-container">
                    <h3>Bar Chart (Simple)</h3>
                    <div data-chart="bar" data-set1="5, 10, 15, 20" data-labels="Jan, Feb, Mar, Apr"
                        data-size="400x300"></div>
                </div>
                <!-- Bar Chart: Complex -->
                <div class="chart-container">
                    <h3>Bar Chart (Stacked, Gradient, Zoom)</h3>
                    <div data-chart="bar" data-set1="10, 20, 30, 40" data-set2="15, 25, 35, 45"
                        data-labels="Mon, Tue, Wed, Thu" data-set2-labels="Series1, Series2"
                        data-settings="stacked,gradient,labels,tooltip,legend,zoom,horizontal"
                        data-rich="shadow,size:12" data-xaxis-name="Value" data-yaxis-name="Day"
                        data-size="400x300"></div>
                </div>
                <!-- Line Chart: With Confidence Band -->
                <div class="chart-container">
                    <h3>Line Chart (Confidence Band, Smooth)</h3>
                    <div data-chart="line" data-set1="10, 20, 30, 40" data-set2="12, 22, 32, 42"
                        data-set3="8, 18, 28, 38" data-labels="Q1, Q2, Q3, Q4"
                        data-settings="confidenceband,smooth,gradient,labels,area" data-rich="color:#0000FF"
                        data-size="400x300"></div>
                </div>
                <!-- Scatter Chart: Simple -->
                <div class="chart-container">
                    <h3>Scatter Chart (Simple)</h3>
                    <div data-chart="scatter" data-set1="1,2;3,4;5,6" data-size="400x300"></div>
                </div>
                <!-- EffectScatter Chart: Bubble -->
                <div class="chart-container">
                    <h3>EffectScatter Chart (Bubble, Fisheye)</h3>
                    <div data-chart="effectscatter" data-set1="1,2,10;3,4,20;5,6,30"
                        data-settings="bubble,fisheye,gradient,labels,effect" data-rich="bold" data-size="400x300">
                    </div>
                </div>
                <!-- Radar Chart -->
                <div class="chart-container">
                    <h3>Radar Chart (Area, Gradient)</h3>
                    <div data-chart="radar" data-set1="65, 80, 90" data-set2="70, 85, 95"
                        data-labels="Speed, Power, Accuracy" data-set2-labels="Player1, Player2"
                        data-settings="area,gradient,labels,tooltip" data-rich="size:14" data-size="400x300"></div>
                </div>
                <!-- Gauge Chart: Simple -->
                <div class="chart-container">
                    <h3>Gauge Chart (Simple)</h3>
                    <div data-chart="gauge" data-set1="75" data-title="Score" data-unit="%" data-size="400x300">
                    </div>
                </div>
                <!-- Gauge Chart: Car Style -->
                <div class="chart-container">
                    <h3>Gauge Chart (Car, Rich Text)</h3>
                    <div data-chart="gauge" data-set1="120" data-settings="car,labels,progress"
                        data-rich="bold,color:#FF0000,size:20" data-unit="km/h" data-size="400x300"></div>
                </div>
                <!-- Gauge Chart: Multi-Title -->
                <div class="chart-container">
                    <h3>Gauge Chart (Multi-Title)</h3>
                    <div data-chart="gauge" data-set1="80" data-set2="90" data-set3="70"
                        data-set2-labels="Metric1, Metric2, Metric3" data-settings="multi-title,gradient"
                        data-rich="underline" data-size="400x300"></div>
                </div>
                <!-- Heatmap Chart -->
                <div class="chart-container">
                    <h3>Heatmap Chart (VisualMap)</h3>
                    <div data-chart="heatmap" data-set1="0,0,10;0,1,20;1,0,15;1,1,25" data-labels-x="Mon, Tue"
                        data-labels-y="AM, PM" data-settings="visualmap,labels,gradient" data-rich="color:#FFFFFF"
                        data-size="400x300"></div>
                </div>
                <!-- Candlestick Chart -->
                <div class="chart-container">
                    <h3>Candlestick Chart (Breaks)</h3>
                    <div data-chart="candlestick" data-set1="20,30,10,15;30,40,25,30;40,50,35,45"
                        data-labels="Day1, Day2, Day3" data-settings="breaks,labels" data-size="400x300"></div>
                </div>
                <!-- Funnel Chart -->
                <div class="chart-container">
                    <h3>Funnel Chart (Gradient)</h3>
                    <div data-chart="funnel" data-set1="100, 80, 60, 40"
                        data-labels="Lead, Prospect, Opportunity, Customer" data-settings="gradient,labels"
                        data-rich="bold" data-size="400x300"></div>
                </div>
                <!-- Treemap Chart -->
                <div class="chart-container">
                    <h3>Treemap Chart (Show Parent)</h3>
                    <div data-chart="treemap"
                        data-set1='{"name":"A","value":40};{"name":"B","value":30};{"name":"C","value":20}'
                        data-settings="showparent,gradient,labels" data-rich="size:12" data-size="400x300"></div>
                </div>
                <!-- Sunburst Chart -->
                <div class="chart-container">
                    <h3>Sunburst Chart (Emphasis)</h3>
                    <div data-chart="sunburst"
                        data-set1='{"name":"Root","children":[{"name":"A","value":30},{"name":"B","children":[{"name":"B1","value":10}]}]}'
                        data-settings="emphasis,gradient,labels" data-rich="italic" data-size="400x300"></div>
                </div>
                <!-- Sankey Chart -->
                <div class="chart-container">
                    <h3>Sankey Chart (Vertical)</h3>
                    <div data-chart="sankey"
                        data-set1='{"nodes":[{"name":"A"},{"name":"B"},{"name":"C"}],"links":[{"source":"A","target":"B","value":10},{"source":"B","target":"C","value":5}]}'
                        data-settings="vertical,gradient,labels" data-rich="bold" data-size="400x300"></div>
                </div>
                <!-- Boxplot Chart -->
                <div class="chart-container">
                    <h3>Boxplot Chart</h3>
                    <div data-chart="boxplot" data-set1="10,20,30,40,50;15,25,35,45,55" data-labels="Group1, Group2"
                        data-settings="labels,gradient" data-rich="size:14" data-size="400x300"></div>
                </div>
                <!-- Parallel Chart -->
                <div class="chart-container">
                    <h3>Parallel Chart</h3>
                    <div data-chart="parallel" data-set1="1,2,3;4,5,6;7,8,9" data-labels="Axis1, Axis2, Axis3"
                        data-settings="labels" data-rich="color:#800080" data-size="400x300"></div>
                </div>
                <!-- Lines Chart -->
                <div class="chart-container">
                    <h3>Lines Chart (Effect)</h3>
                    <div data-chart="lines"
                        data-set1='{"coords":"0,0;1,1;2,2","name":"Line1"};{"coords":"2,2;3,3","name":"Line2"}'
                        data-settings="effect,gradient,labels" data-rich="bold" data-xaxis-name="X"
                        data-yaxis-name="Y" data-size="400x300"></div>
                </div>
                <!-- Graph Chart -->
                <div class="chart-container">
                    <h3>Graph Chart (Draggable)</h3>
                    <div data-chart="graph" data-set1='{"name":"Node1","value":10};{"name":"Node2","value":20}'
                        data-set2='{"source":"Node1","target":"Node2","value":1}'
                        data-settings="draggable,gradient,labels,roam" data-rich="size:12" data-size="400x300"></div>
                </div>
                <!-- Tree Chart -->
                <div class="chart-container">
                    <h3>Tree Chart (Radial)</h3>
                    <div data-chart="tree"
                        data-set1='{"name":"Root","children":[{"name":"A"},{"name":"B","children":[{"name":"B1"}]}]}'
                        data-settings="radial,gradient,labels" data-rich="italic" data-size="400x300"></div>
                </div>
                <!-- ThemeRiver Chart -->
                <div class="chart-container">
                    <h3>ThemeRiver Chart</h3>
                    <div data-chart="themeriver"
                        data-set1='2023-01-01,10,Stream1;2023-01-02,15,Stream1;2023-01-01,5,Stream2'
                        data-settings="gradient,labels" data-rich="bold" data-size="400x300"></div>
                </div>
                <!-- PictorialBar Chart -->
                <div class="chart-container">
                    <h3>PictorialBar Chart (Dotted)</h3>
                    <div data-chart="pictorialbar" data-set1="10, 20, 30" data-labels="A, B, C"
                        data-symbol="triangle" data-settings="dotted,gradient,labels" data-rich="color:#008000"
                        data-size="400x300"></div>
                </div>
                <!-- Custom Chart -->
                <div class="chart-container">
                    <h3>Custom Chart (Rectangles)</h3>
                    <div data-chart="custom" data-set1="1,2;3,4;5,6"
                        data-render-item="if (!params || !Array.isArray(params.data) || params.data.length < 2 || !api) return { type: 'group' }; const [x, y] = api.coord(params.data); return { type: 'rect', shape: { x: x-10, y: y-10, width: 20, height: 20 }, style: { fill: params.data[0] > 3 ? '#ff0000' : '#00ff00' } };"
                        data-settings="gradient,labels" data-rich="bold" data-size="400x300"></div>
                </div>
                <!-- Edge Case: Empty Dataset -->
                <div class="chart-container">
                    <h3>Bar Chart (Empty Dataset)</h3>
                    <div data-chart="bar" data-set1="" data-size="400x300"></div>
                </div>
                <!-- Auto-Detect Chart -->
                <div class="chart-container">
                    <h3>Auto-Detect Chart (Scatter)</h3>
                    <div data-chart-auto data-set1="1,2;3,4;5,6" data-settings="gradient,labels" data-rich="italic"
                        data-size="400x300"></div>
                </div>
                <!-- Basic Line Chart -->
                <div class="chart-container">
                    <h3>Basic Line Chart</h3>
                    <div data-chart="line" data-title="Sales Trend" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Smoothed Line Chart -->
                <div class="chart-container">
                    <h3>Smoothed Line Chart</h3>
                    <div data-chart="line" data-title="Smooth Revenue" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="220, 182, 191, 234, 290" data-settings="tooltip,legend,smooth,animation"></div>
                </div>
                <!-- Basic Area Chart -->
                <div class="chart-container">
                    <h3>Basic Area Chart</h3>
                    <div data-chart="line" data-title="Area Sales" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,area,animation"></div>
                </div>
                <!-- Stacked Line Chart -->
                <div class="chart-container">
                    <h3>Stacked Line Chart</h3>
                    <div data-chart="line" data-title="Stacked Sales" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-set2="220, 182, 191, 234, 290"
                        data-set2-labels="Product A, Product B" data-settings="tooltip,legend,stacked,animation">
                    </div>
                </div>
                <!-- Stacked Area Chart -->
                <div class="chart-container">
                    <h3>Stacked Area Chart</h3>
                    <div data-chart="line" data-title="Stacked Area" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-set2="220, 182, 191, 234, 290"
                        data-set2-labels="Product A, Product B" data-settings="tooltip,legend,stacked,area,animation">
                    </div>
                </div>
                <!-- Gradient Stacked Area Chart -->
                <div class="chart-container">
                    <h3>Gradient Stacked Area Chart</h3>
                    <div data-chart="line" data-title="Gradient Area" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-set2="220, 182, 191, 234, 290"
                        data-set2-labels="Product A, Product B"
                        data-settings="tooltip,legend,stacked,area,gradient,animation"></div>
                </div>
                <!-- Bump Chart (Ranking) -->
                <div class="chart-container">
                    <h3>Bump Chart (Ranking)</h3>
                    <div data-chart="line" data-title="Team Rankings" data-labels="Q1, Q2, Q3, Q4"
                        data-set1="1, 2, 3, 1" data-set2="2, 1, 2, 3" data-set3="3, 3, 1, 2"
                        data-set2-labels="Team A, Team B, Team C" data-settings="tooltip,legend,bump,animation"></div>
                </div>
                <!-- Temperature Change in the Coming Week -->
                <div class="chart-container">
                    <h3>Temperature Change in the Coming Week</h3>
                    <div data-chart="line" data-title="Temperature Forecast"
                        data-labels="Mon, Tue, Wed, Thu, Fri, Sat, Sun" data-set1="20, 22, 21, 23, 25, 24, 26"
                        data-settings="tooltip,legend,markline,animation" data-unit="°C"></div>
                </div>
                <!-- Area Pieces -->
                <div class="chart-container">
                    <h3>Area Pieces</h3>
                    <div data-chart="line" data-title="Sales Thresholds" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,markarea,animation"></div>
                </div>
                <!-- Data Zoom Line -->
                <div class="chart-container">
                    <h3>Data Zoom Line</h3>
                    <div data-chart="line" data-title="Zoomable Sales" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,zoom,animation"></div>
                </div>
                <!-- Line Gradient -->
                <div class="chart-container">
                    <h3>Line Gradient</h3>
                    <div data-chart="line" data-title="Gradient Line" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,gradient,animation"></div>
                </div>
                <!-- Electricity Distribution -->
                <div class="chart-container">
                    <h3>Electricity Distribution</h3>
                    <div data-chart="line" data-title="Electricity Usage" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="300, 280, 350, 400, 380" data-settings="tooltip,legend,area,animation"
                        data-unit="kWh"></div>
                </div>
                <!-- Large Scale Area Chart -->
                <div class="chart-container">
                    <h3>Large Scale Area Chart</h3>
                    <div data-chart="line" data-title="Large Scale Data" data-labels="1, 2, 3, 4, 5, 6, 7, 8, 9, 10"
                        data-set1="120, 132, 101, 134, 90, 200, 210, 180, 160, 150"
                        data-settings="tooltip,legend,area,zoom,animation"></div>
                </div>
                <!-- Confidence Band -->
                <div class="chart-container">
                    <h3>Confidence Band</h3>
                    <div data-chart="line" data-title="Confidence Band" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-set2="130, 142, 111, 144, 100"
                        data-set3="10, 10, 10, 10, 10" data-set2-labels="Mean, Upper, Lower"
                        data-settings="tooltip,legend,confidenceband,animation"></div>
                </div>
                <!-- Rainfall vs Evaporation -->
                <div class="chart-container">
                    <h3>Rainfall vs Evaporation</h3>
                    <div data-chart="line" data-title="Rainfall vs Evaporation" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="2.0, 4.9, 7.0, 23.2, 25.6" data-set2="2.6, 5.9, 9.0, 26.4, 28.7"
                        data-set2-labels="Rainfall, Evaporation" data-settings="tooltip,legend,area,animation"
                        data-unit="mm"></div>
                </div>
                <!-- Beijing AQI -->
                <div class="chart-container">
                    <h3>Beijing AQI</h3>
                    <div data-chart="line" data-title="Beijing AQI" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="150, 120, 130, 110, 140" data-settings="tooltip,legend,area,animation"
                        data-unit="AQI"></div>
                </div>
                <!-- Time Axis Area Chart -->
                <div class="chart-container">
                    <h3>Time Axis Area Chart</h3>
                    <div data-chart="line" data-title="Time Axis Sales"
                        data-labels="2023-01-01, 2023-02-01, 2023-03-01, 2023-04-01, 2023-05-01"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,area,timeaxis,animation">
                    </div>
                </div>
                <!-- Dynamic Data with Time Axis -->
                <div class="chart-container">
                    <h3>Dynamic Data with Time Axis</h3>
                    <div data-chart="line" data-title="Dynamic Sales"
                        data-labels="2023-01-01, 2023-02-01, 2023-03-01, 2023-04-01, 2023-05-01"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,zoom,timeaxis,animation">
                    </div>
                </div>
                <!-- Function Plot (Sine Wave) -->
                <div class="chart-container">
                    <h3>Function Plot (Sine Wave)</h3>
                    <div data-chart="line" data-title="Sine Wave" data-labels="-10, -8, -6, -4, -2, 0, 2, 4, 6, 8, 10"
                        data-set1="0.54, 0.99, 0.28, -0.76, -0.91, 0, 0.91, 0.76, -0.28, -0.99, -0.54"
                        data-settings="tooltip,legend,smooth,animation"></div>
                </div>
                <!-- Line Race -->
                <div class="chart-container">
                    <h3>Line Race</h3>
                    <div data-chart="line" data-title="Line Race" data-labels="2020, 2021, 2022, 2023, 2024"
                        data-set1="100, 120, 150, 130, 140" data-set2="80, 110, 130, 140, 150"
                        data-set2-labels="Team A, Team B" data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Line with Marklines -->
                <div class="chart-container">
                    <h3>Line with Marklines</h3>
                    <div data-chart="line" data-title="Sales with Avg" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,markline,animation"></div>
                </div>
                <!-- Step Line -->
                <div class="chart-container">
                    <h3>Step Line</h3>
                    <div data-chart="line" data-title="Step Line" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,step,animation"></div>
                </div>
                <!-- Line Y Category (Horizontal) -->
                <div class="chart-container">
                    <h3>Line Y Category (Horizontal)</h3>
                    <div data-chart="line" data-title="Y Category Line" data-labels="A, B, C, D, E"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,horizontal,animation"></div>
                </div>
                <!-- Fisheye Line Chart -->
                <div class="chart-container">
                    <h3>Fisheye Line Chart</h3>
                    <div data-chart="line" data-title="Fisheye Line" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,fisheye,animation"></div>
                </div>
                <!-- Log Axis Line -->
                <div class="chart-container">
                    <h3>Log Axis Line</h3>
                    <div data-chart="line" data-title="Logarithmic Sales" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="1000, 2000, 1500, 3000, 2500" data-settings="tooltip,legend,logaxis,animation">
                    </div>
                </div>
                <!-- Basic Bar -->
                <div class="chart-container">
                    <h3>Basic Bar</h3>
                    <div data-chart="bar" data-title="Sales by Category" data-labels="A, B, C, D, E"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Waterfall Chart -->
                <div class="chart-container">
                    <h3>Waterfall Chart</h3>
                    <div data-chart="bar" data-title="Waterfall Profit" data-labels="Start, Jan, Feb, Mar, End"
                        data-set1="0, 120, 252, 353, 487" data-settings="tooltip,legend,waterfall,animation"></div>
                </div>
                <!-- Bar with Negative Values -->
                <div class="chart-container">
                    <h3>Bar with Negative Values</h3>
                    <div data-chart="bar" data-title="Profit/Loss" data-labels="A, B, C, D, E"
                        data-set1="120, -132, 101, -134, 90" data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Stacked Bar -->
                <div class="chart-container">
                    <h3>Stacked Bar</h3>
                    <div data-chart="bar" data-title="Stacked Columns" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-set2="220, 182, 191, 234, 290"
                        data-set2-labels="Product A, Product B" data-settings="tooltip,legend,stacked,animation">
                    </div>
                </div>
                <!-- Bar with Gradient -->
                <div class="chart-container">
                    <h3>Bar with Gradient</h3>
                    <div data-chart="bar" data-title="Gradient Bar" data-labels="A, B, C, D, E"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,gradient,animation"></div>
                </div>
                <!-- Bar with Labels -->
                <div class="chart-container">
                    <h3>Bar with Labels</h3>
                    <div data-chart="bar" data-title="Labeled Bar"
                        data-labels="Category A, Category B, Category C, Category D, Category E"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,labels,animation"></div>
                </div>
                <!-- Zoomable Bar -->
                <div class="chart-container">
                    <h3>Zoomable Bar</h3>
                    <div data-chart="bar" data-title="Zoomable Bar" data-labels="Jan, Feb, Mar, Apr, May"
                        data-set1="120, 132, 101, 134, 90" data-settings="tooltip,legend,zoom,animation"></div>
                </div>
                <!-- Bar with Brush -->
                <div class="chart-container">
                    <h3>Bar with Brush</h3>
                    <div data-chart="bar" data-title="Brush Bar" data-labels="A, B, C, D, E"
                        data-set1="1200, 132, 101, 134, 90" data-settings="tooltip,legend,brush,animation"></div>
                </div>
                <!-- Large Scale Bar -->
                <div class="chart-container">
                    <h3>Large Scale Bar</h3>
                    <div data-chart="bar" data-title="Large Scale Bar" data-labels="1, 2, 3, 4, 5, 6, 7, 8, 9, 10"
                        data-set1="120, 132, 101, 134, 90, 200, 210, 180, 160, 150"
                        data-settings="tooltip,legend,zoom,animation"></div>
                </div>
                <!-- Bar Race -->
                <div class="chart-container">
                    <h3>Bar Race</h3>
                    <div data-chart="bar" data-title="Bar Race" data-labels="2020, 2021, 2022, 2023, 2024"
                        data-set1="100, 120, 150, 130, 140" data-set2="80, 110, 130, 140, 150"
                        data-set2-labels="Team A, Team B" data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Matrix Bar Chart -->
                <div class="chart-container">
                    <h3>Matrix Bar Chart</h3>
                    <div data-chart="bar" data-title="Matrix Bar" data-labels="A, B, C" data-set1="120, 132, 101"
                        data-set2="220, 182, 191" data-set2-labels="Set 1, Set 2"
                        data-settings="tooltip,legend,matrix,animation"></div>
                </div>
                <!-- Basic Pie Chart -->
                <div class="chart-container">
                    <h3>Basic Pie Chart</h3>
                    <div data-chart="pie" data-title="Website Referrals" data-labels="Direct, Search, Social, Ads"
                        data-set1="335, 310, 234, 135" data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Doughnut Chart -->
                <div class="chart-container">
                    <h3>Doughnut Chart</h3>
                    <div data-chart="donut" data-title="Simple Doughnut" data-labels="A, B, C, D"
                        data-set1="335, 310, 234, 135" data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Nightingale Chart -->
                <div class="chart-container">
                    <h3>Nightingale Chart</h3>
                    <div data-chart="pie" data-title="Nightingale Chart" data-labels="A, B, C, D"
                        data-set1="335, 310, 234, 135" data-settings="tooltip,legend,rose,animation"></div>
                </div>
                <!-- Pie with Labels -->
                <div class="chart-container">
                    <h3>Pie with Labels</h3>
                    <div data-chart="pie" data-title="Labeled Pie" data-labels="A, B, C, D"
                        data-set1="335, 310, 234, 135" data-settings="tooltip,legend,labels,animation"></div>
                </div>
                <!-- Gradient Pie -->
                <div class="chart-container">
                    <h3>Gradient Pie</h3>
                    <div data-chart="pie" data-title="Gradient Pie" data-labels="A, B, C, D"
                        data-set1="335, 310, 234, 135" data-settings="tooltip,legend,gradient,animation"></div>
                </div>
                <!-- Basic Scatter Chart -->
                <div class="chart-container">
                    <h3>Basic Scatter Chart</h3>
                    <div data-chart="scatter" data-title="Sales vs Profit" data-set1="1,120;2,132;3,101;4,134;5,90"
                        data-settings="tooltip,legend,animation" data-xaxis-name="Sales" data-yaxis-name="Profit">
                    </div>
                </div>
                <!-- Effect Scatter Chart -->
                <div class="chart-container">
                    <h3>Effect Scatter Chart</h3>
                    <div data-chart="effectscatter" data-title="Effect Scatter"
                        data-set1="1,120;2,132;3,101;4,134;5,90" data-settings="tooltip,legend,effect,animation"
                        data-xaxis-name="X" data-yaxis-name="Y"></div>
                </div>
                <!-- Bubble Chart -->
                <div class="chart-container">
                    <h3>Bubble Chart</h3>
                    <div data-chart="scatter" data-title="Bubble Chart"
                        data-set1="1,120,10;2,132,15;3,101,8;4,134,12;5,90,20"
                        data-settings="tooltip,legend,bubble,animation" data-xaxis-name="X" data-yaxis-name="Y">
                    </div>
                </div>
                <!-- Scatter with Labels -->
                <div class="chart-container">
                    <h3>Scatter with Labels</h3>
                    <div data-chart="scatter" data-title="Labeled Scatter" data-set1="1,120;2,132;3,101;4,134;5,90"
                        data-settings="tooltip,legend,labels,animation" data-xaxis-name="X" data-yaxis-name="Y">
                    </div>
                </div>
                <!-- Matrix Scatter Chart -->
                <div class="chart-container">
                    <h3>Matrix Scatter Chart</h3>
                    <div data-chart="scatter" data-title="Correlation Matrix" data-set1="1,120;2,132;3,101;4,134;5,90"
                        data-set2="1,130;2,142;3,111;4,144;5,100" data-set2-labels="Set 1, Set 2"
                        data-settings="tooltip,legend,matrix,animation"></div>
                </div>
                <!-- Fisheye Scatter Chart -->
                <div class="chart-container">
                    <h3>Fisheye Scatter Chart</h3>
                    <div data-chart="scatter" data-title="Fisheye Scatter" data-set1="1,120;2,132;3,101;4,134;5,90"
                        data-settings="tooltip,legend,fisheye,animation" data-xaxis-name="X" data-yaxis-name="Y">
                    </div>
                </div>
                <!-- Basic Radar Chart -->
                <div class="chart-container">
                    <h3>Basic Radar Chart</h3>
                    <div data-chart="radar" data-title="Performance Metrics"
                        data-labels="Speed, Power, Accuracy, Stamina, Agility" data-set1="80, 70, 90, 60, 85"
                        data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Radar with Area -->
                <div class="chart-container">
                    <h3>Radar with Area</h3>
                    <div data-chart="radar" data-title="AQI Metrics" data-labels="PM2.5, PM10, SO2, NO2, O3"
                        data-set1="150, 120, 30, 40, 50" data-settings="tooltip,legend,area,animation"></div>
                </div>
                <!-- Multiple Radar -->
                <div class="chart-container">
                    <h3>Multiple Radar</h3>
                    <div data-chart="radar" data-title="Multiple Radars" data-labels="A, B, C, D, E"
                        data-set1="80, 70, 90, 60, 85" data-set2="70, 80, 60, 90, 75"
                        data-set2-labels="Team A, Team B" data-settings="tooltip,legend,area,animation"></div>
                </div>
                <!-- Basic Boxplot -->
                <div class="chart-container">
                    <h3>Basic Boxplot</h3>
                    <div data-chart="boxplot" data-title="Sales Boxplot" data-labels="Region1, Region2, Region3"
                        data-set1="100,120,110,130,140;80,90,100,110,120;120,130,140,150,160"
                        data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Boxplot with Labels -->
                <div class="chart-container">
                    <h3>Boxplot with Labels</h3>
                    <div data-chart="boxplot" data-title="Labeled Boxplot" data-labels="Region1, Region2, Region3"
                        data-set1="100,120,110,130,140;80,90,100,110,120;120,130,140,150,160"
                        data-settings="tooltip,legend,labels,animation"></div>
                </div>
                <!-- Basic Heatmap -->
                <div class="chart-container">
                    <h3>Basic Heatmap</h3>
                    <div data-chart="heatmap" data-title="Cartesian Heatmap" data-labels-x="Mon, Tue, Wed"
                        data-labels-y="Morning, Afternoon" data-set1="0,0,10;0,1,20;1,0,15;1,1,25;2,0,30;2,1,35"
                        data-settings="tooltip,legend,visualmap,animation" data-xaxis-name="Day"
                        data-yaxis-name="Time">
                    </div>
                </div>
                <!-- Large Heatmap -->
                <div class="chart-container">
                    <h3>Large Heatmap</h3>
                    <div data-chart="heatmap" data-title="Large Heatmap" data-labels-x="1, 2, 3"
                        data-labels-y="A, B, C"
                        data-set1="0,0,100;0,1,200;0,2,150;1,0,120;1,1,180;1,2,170;2,0,160;2,1,190;2,2,140"
                        data-settings="tooltip,legend,visualmap,animation" data-xaxis-name="X" data-yaxis-name="Y">
                    </div>
                </div>
                <!-- Basic Candlestick -->
                <div class="chart-container">
                    <h3>Basic Candlestick</h3>
                    <div data-chart="candlestick" data-title="Stock Prices" data-labels="Day1, Day2, Day3, Day4, Day5"
                        data-set1="100,110,95,105;110,115,100,112;112,120,110,115;115,118,112,117;117,120,115,118"
                        data-settings="tooltip,legend,animation" data-yaxis-name="Price"></div>
                </div>
                <!-- Candlestick with Brush -->
                <div class="chart-container">
                    <h3>Candlestick with Brush</h3>
                    <div data-chart="candlestick" data-title="Stock Prices with Brush"
                        data-labels="Day1, Day2, Day3, Day4, Day5"
                        data-set1="100,110,95,105;110,115,100,112;112,120,110,115;115,118,112,117;117,120,115,118"
                        data-settings="tooltip,legend,brush,animation" data-yaxis-name="Price" data-size="400x300">
                    </div>
                </div>
                <!-- Basic Funnel Chart -->
                <div class="chart-container">
                    <h3>Basic Funnel Chart</h3>
                    <div data-chart="funnel" data-title="Sales Funnel"
                        data-labels="Lead, Prospect, Opportunity, Customer, Sale" data-set1="100, 80, 60, 40, 20"
                        data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Funnel with Labels -->
                <div class="chart-container">
                    <h3>Funnel with Labels</h3>
                    <div data-chart="funnel" data-title="Labeled Funnel"
                        data-labels="Lead, Prospect, Opportunity, Customer, Sale" data-set1="100, 80, 60, 40, 20"
                        data-settings="tooltip,legend,labels,animation"></div>
                </div>
                <!-- Basic Treemap -->
                <div class="chart-container">
                    <h3>Basic Treemap</h3>
                    <div data-chart="treemap" data-title="Category Breakdown"
                        data-set1='{"name":"A","value":40};{"name":"B","value":30};{"name":"C","value":20}'
                        data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Treemap with Show Parent -->
                <div class="chart-container">
                    <h3>Treemap with Show Parent</h3>
                    <div data-chart="treemap" data-title="Hierarchical Treemap"
                        data-set1='{"name":"Parent","children":[{"name":"A","value":40},{"name":"B","value":30},{"name":"C","value":20}]}'
                        data-settings="tooltip,legend,showparent,animation"></div>
                </div>
                <!-- Basic Sunburst -->
                <div class="chart-container">
                    <h3>Basic Sunburst</h3>
                    <div data-chart="sunburst" data-title="Sunburst Hierarchy"
                        data-set1='{"name":"Root","children":[{"name":"A","value":30},{"name":"B","children":[{"name":"B1","value":10},{"name":"B2","value":20}]}]}'
                        data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Sunburst with Emphasis -->
                <div class="chart-container">
                    <h3>Sunburst with Emphasis</h3>
                    <div data-chart="sunburst" data-title="Highlighted Sunburst"
                        data-set1='{"name":"Root","children":[{"name":"A","value":30},{"name":"B","children":[{"name":"B1","value":10},{"name":"B2","value":20}]}]}'
                        data-settings="tooltip,legend,emphasis,animation"></div>
                </div>
                <!-- Basic Sankey -->
                <div class="chart-container">
                    <h3>Basic Sankey</h3>
                    <div data-chart="sankey" data-title="Flow Diagram"
                        data-set1='{"nodes":[{"name":"A"},{"name":"B"},{"name":"C"}],"links":[{"source":"A","target":"B","value":10},{"source":"B","target":"C","value":5}]}'
                        data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Sankey Vertical -->
                <div class="chart-container">
                    <h3>Sankey Vertical</h3>
                    <div data-chart="sankey" data-title="Vertical Flow"
                        data-set1='{"nodes":[{"name":"A"},{"name":"B"},{"name":"C"}],"links":[{"source":"A","target":"B","value":10},{"source":"B","target":"C","value":5}]}'
                        data-settings="tooltip,legend,vertical,animation"></div>
                </div>
                <!-- Basic Parallel Chart -->
                <div class="chart-container">
                    <h3>Basic Parallel Chart</h3>
                    <div data-chart="parallel" data-title="Parallel Coordinates"
                        data-labels="Metric1, Metric2, Metric3" data-set1="1,2,3;4,5,6;7,8,9"
                        data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Basic Lines Chart -->
                <div class="chart-container">
                    <h3>Basic Lines Chart</h3>
                    <div data-chart="lines" data-title="Path Lines"
                        data-set1='{"coords":"0,0;1,1;2,2","name":"Line1"};{"coords":"2,2;3,3;4,4","name":"Line2"}'
                        data-settings="tooltip,legend,animation" data-xaxis-name="X" data-yaxis-name="Y"></div>
                </div>
                <!-- Lines with Effect -->
                <div class="chart-container">
                    <h3>Lines with Effect</h3>
                    <div data-chart="lines" data-title="Effect Lines"
                        data-set1='{"coords":"0,0;1,1;2,2","name":"Line1"};{"coords":"2,2;3,3;4,4","name":"Line2"}'
                        data-settings="tooltip,legend,effect,animation" data-xaxis-name="X" data-yaxis-name="Y">
                    </div>
                </div>
                <!-- Basic Graph Chart -->
                <div class="chart-container">
                    <h3>Basic Graph Chart</h3>
                    <div data-chart="graph" data-title="Network Graph"
                        data-set1='{"name":"Node1","value":10};{"name":"Node2","value":20};{"name":"Node3","value":30}'
                        data-set2='{"source":"Node1","target":"Node2","value":1};{"source":"Node2","target":"Node3","value":2}'
                        data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Graph with Draggable Nodes -->
                <div class="chart-container">
                    <h3>Graph with Draggable Nodes</h3>
                    <div data-chart="graph" data-title="Draggable Network"
                        data-set1='{"name":"Node1","value":10};{"name":"Node2","value":20};{"name":"Node3","value":30}'
                        data-set2='{"source":"Node1","target":"Node2","value":1};{"source":"Node2","target":"Node3","value":2}'
                        data-settings="tooltip,legend,draggable,roam,animation"></div>
                </div>
                <!-- Basic Tree Chart -->
                <div class="chart-container">
                    <h3>Basic Tree Chart</h3>
                    <div data-chart="tree" data-title="Tree Hierarchy"
                        data-set1='{"name":"Root","children":[{"name":"A","value":10},{"name":"B","children":[{"name":"B1","value":5}]}]}'
                        data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Radial Tree Chart -->
                <div class="chart-container">
                    <h3>Radial Tree Chart</h3>
                    <div data-chart="tree" data-title="Radial Tree"
                        data-set1='{"name":"Root","children":[{"name":"A","value":10},{"name":"B","children":[{"name":"B1","value":5}]}]}'
                        data-settings="tooltip,legend,radial,animation"></div>
                </div>
                <!-- Basic ThemeRiver Chart -->
                <div class="chart-container">
                    <h3>Basic ThemeRiver Chart</h3>
                    <div data-chart="themeriver" data-title="Stream Flow"
                        data-set1='2023-01-01,10,Stream1;2023-01-02,15,Stream1;2023-01-01,5,Stream2;2023-01-02,8,Stream2'
                        data-settings="tooltip,legend,animation"></div>
                </div>
                <!-- Basic PictorialBar Chart -->
                <div class="chart-container">
                    <h3>Basic PictorialBar Chart</h3>
                    <div data-chart="pictorialbar" data-title="Pictorial Bar" data-labels="A, B, C"
                        data-set1="10, 20, 30" data-symbol="triangle" data-settings="tooltip,legend,animation"
                        data-xaxis-name="Category" data-yaxis-name="Value"></div>
                </div>
                <!-- PictorialBar with Dotted Style -->
                <div class="chart-container">
                    <h3>PictorialBar with Dotted Style</h3>
                    <div data-chart="pictorialbar" data-title="Dotted Pictorial Bar" data-labels="A, B, C"
                        data-set1="10, 20, 30" data-symbol="triangle" data-settings="tooltip,legend,dotted,animation"
                        data-xaxis-name="Category" data-yaxis-name="Value"></div>
                </div>
                <!-- Basic Custom Chart -->
                <div class="chart-container">
                    <h3>Basic Custom Chart</h3>
                    <div data-chart="custom" data-title="Custom Shapes" data-set1="1,2;3,4;5,6"
                        data-render-item="if (!params || !Array.isArray(params.data) || params.data.length < 2 || !api) return { type: 'group' }; const [x, y] = api.coord(params.data); return { type: 'rect', shape: { x: x-10, y: y-10, width: 20, height: 20 }, style: { fill: params.data[0] > 3 ? '#ff0000' : '#00ff00' } };"
                        data-settings="tooltip,legend,animation" data-xaxis-name="X" data-yaxis-name="Y"></div>
                </div>
                <!-- Custom Chart with Circles -->
                <div class="chart-container">
                    <h3>Custom Chart with Circles</h3>
                    <div data-chart="custom" data-title="Custom Circles" data-set1="1,2;3,4;5,6"
                        data-render-item="if (!params || !Array.isArray(params.data) || params.data.length < 2 || !api) return { type: 'group' }; const [x, y] = api.coord(params.data); return { type: 'circle', shape: { cx: x, cy: y, r: 10 }, style: { fill: params.data[0] > 3 ? '#ff4500' : '#4682b4' } };"
                        data-settings="tooltip,legend,animation" data-xaxis-name="X" data-yaxis-name="Y"></div>
                </div>
                <!-- Auto-Detect: Line -->
                <div class="chart-container">
                    <h3>Auto-Detect (Line)</h3>
                    <div data-chart-auto data-set1="10, 20, 30, 40" data-labels="Jan, Feb, Mar, Apr"
                        data-settings="gradient,labels,tooltip" data-rich="bold,size:12" data-size="400x300">
                    </div>
                </div>
            </div>
        {{--************************************************************************************************
        *                                                                                                  *
        *                             >>> MODIFY THIS SECTION (END) <<<                                    *
        *                                                                                                  *
        ************************************************************************************************--}}
    </div>
</div>
@endsection