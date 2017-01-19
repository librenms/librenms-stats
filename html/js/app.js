function fetch_chart_data(chart, div) {
    $.ajax({
        type: "GET",
        dataType: 'json',
        url: "./data.php",
        data: {id: div}
    })
        .done(function (data) {
            chart.setData(data);
        })
        .fail(function (jqXHR, textStatus) {
            // If there is no communication between the server, show an error
            console.log(textStatus);
        });
}
