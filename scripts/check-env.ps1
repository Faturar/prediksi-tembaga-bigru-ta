Write-Host "Checking local tools..."

$commands = @(
    @{ Name = "Git"; Command = "git --version" },
    @{ Name = "PHP"; Command = "php -v" },
    @{ Name = "Python"; Command = "py --version" },
    @{ Name = "MySQL"; Command = "mysql --version" }
)

foreach ($item in $commands) {
    Write-Host "`n[$($item.Name)]"
    try {
        Invoke-Expression $item.Command
    } catch {
        Write-Host "Not available on PATH"
    }
}
