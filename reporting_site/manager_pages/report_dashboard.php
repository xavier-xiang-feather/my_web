<script>
function exportReport(reportId){
    // 1. 查找对应的报告路径
    const reports = [
        {id: 1, path: "/../report_pages/browser_report.php"},
        {id: 2, path: "/../report_pages/mouse_event_report.php"},
        {id: 3, path: "/../report_pages/performance_report.php"}
    ];
    const report = reports.find(r => r.id === reportId);
    if(!report) return;

    // 2. 创建隐藏 iframe 渲染图表
    let iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = report.path;
    document.body.appendChild(iframe);

    iframe.onload = function() {
        // 给一点点时间让脚本在 iframe 内执行
        setTimeout(() => {
            let chartImage = null;
            try {
                // 调用 iframe 内部刚才定义的 getChartImage 函数
                if(iframe.contentWindow.getChartImage){
                    chartImage = iframe.contentWindow.getChartImage();
                }
            } catch(e) {
                console.error("Cannot access iframe content", e);
            }

            // 3. 执行 Fetch
            fetch("/api/static/export_report.php",{
                method:"POST",
                headers:{ "Content-Type":"application/json" },
                body:JSON.stringify({
                    id:reportId,
                    chart:chartImage
                })
            })
            .then(res=>res.json())
            .then(data=>{
                if(data.status==="success"){
                    window.open(data.url);
                }else{
                    alert(data.message);
                }
                document.body.removeChild(iframe);
            })
            .catch(err => {
                alert("Export failed");
                document.body.removeChild(iframe);
            });
        }, 500);
    };
}
</script>