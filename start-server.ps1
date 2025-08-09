# Простой HTTP сервер на PowerShell
$port = 8080
$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add("http://localhost:$port/")
$listener.Start()

Write-Host "Сервер запущен на http://localhost:$port"
Write-Host "Нажмите Ctrl+C для остановки"

try {
    while ($listener.IsListening) {
        $context = $listener.GetContext()
        $request = $context.Request
        $response = $context.Response
        
        $url = $request.Url.LocalPath
        if ($url -eq "/") { $url = "/index.html" }
        
        $filePath = Join-Path $PSScriptRoot $url.TrimStart('/')
        
        if (Test-Path $filePath -PathType Leaf) {
            $content = Get-Content $filePath -Raw -Encoding UTF8
            $response.ContentType = switch ([System.IO.Path]::GetExtension($filePath)) {
                ".html" { "text/html; charset=utf-8" }
                ".css" { "text/css; charset=utf-8" }
                ".js" { "application/javascript; charset=utf-8" }
                ".json" { "application/json; charset=utf-8" }
                ".svg" { "image/svg+xml" }
                default { "text/plain; charset=utf-8" }
            }
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($content)
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
        } else {
            $response.StatusCode = 404
            $errorContent = "404 - Файл не найден: $url"
            $buffer = [System.Text.Encoding]::UTF8.GetBytes($errorContent)
            $response.ContentLength64 = $buffer.Length
            $response.OutputStream.Write($buffer, 0, $buffer.Length)
        }
        
        $response.OutputStream.Close()
        Write-Host "$($request.HttpMethod) $url - $($response.StatusCode)"
    }
} finally {
    $listener.Stop()
    Write-Host "Сервер остановлен"
}